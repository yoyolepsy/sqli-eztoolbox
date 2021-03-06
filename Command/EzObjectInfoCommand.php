<?php

namespace SQLI\EzToolboxBundle\Command;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EzObjectInfoCommand extends ContainerAwareCommand
{
    /** @var Repository */
    private $repository;
    /** @var ContentService */
    private $contentService;
    /** @var LocationService */
    private $locationService;
    /** @var SearchService */
    private $searchService;

    public function initialize( InputInterface $input, OutputInterface $output )
    {
        $output->setDecorated( true );

        $this->repository      = $this->getContainer()->get( 'ezpublish.api.repository' );
        $this->contentService  = $this->repository->getContentService();
        $this->locationService = $this->repository->getLocationService();
        $this->searchService   = $this->repository->getSearchService();

        // Load and set Administrator User for permissions
        $administratorUser = $this->repository->getUserService()->loadUser( 14 );
        $this->repository->getPermissionResolver()->setCurrentUserReference( $administratorUser );
    }

    protected function configure()
    {
        $this->setName( 'sqli:object:info' )
            ->setDescription( 'Display informations of specified content or location' )
            ->addOption( 'content', null, InputOption::VALUE_OPTIONAL, "Display informations of specified content" )
            ->addOption( 'location', null, InputOption::VALUE_OPTIONAL, "Display informations of specified location" );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        if( $locationId = $input->getOption( 'location' ) )
        {
            $this->displayLocationInfo( $output, $locationId );
        }
        elseif( $contentId = $input->getOption( 'content' ) )
        {
            $this->displayContentInfo( $output, $contentId );
        }
    }

    private function displayLocationInfo( OutputInterface $output, $locationId )
    {
        /** @var Location $location */
        $location = $this->locationService->loadLocation( $locationId );

        // Display location informations
        $output->writeln( "Location ID : $locationId" );
        $output->writeln( "Location name : " . $location->getContentInfo()->name );
        $output->writeln( "Location path string : " . $location->pathString );
        $output->writeln( "Location ancestors :" );
        foreach( $location->path as $ancestorId )
        {
            $ancestorLocation = $this->locationService->loadLocation( $ancestorId );
            $output->writeln( "  $ancestorId : " . $ancestorLocation->getContentInfo()->name );
        }
    }

    private function displayContentInfo( OutputInterface $output, $contentId )
    {
        /** @var Content $content */
        $content        = $this->contentService->loadContent( $contentId );
        $mainLocationId = $content->contentInfo->mainLocationId;

        // Display content informations
        $output->writeln( "<comment>Content ID :</comment> $contentId\n" );
        $output->writeln( "<comment>Main location :</comment>" );
        $this->displayLocationInfo( $output, $mainLocationId );

        // Search other locations
        $query        = new LocationQuery();
        $criterion[]  = new Criterion\ContentId( $contentId );
        $query->query = new Criterion\LogicalAnd( $criterion );

        $results = $this->searchService->findLocations( $query );

        // Display locations
        $output->writeln( "\n<comment>Other locations :</comment>" );
        foreach( $results->searchHits as $locationFound )
        {
            if( $locationFound->valueObject->id != $mainLocationId )
            {
                // It's not main location, display it
                $this->displayLocationInfo( $output, $locationFound->valueObject->id );
                $output->writeln( "" );
            }
        }
    }
}
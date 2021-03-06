<?php

namespace SQLI\EzToolboxBundle\Command;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class PublishAllContentsCommand extends ContainerAwareCommand
{
    const FETCH_LIMIT = 25;
    private $contentClassIdentifier;
    private $repository;
    private $searchService;
    private $contentService;
    private $totalCount;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function initialize( InputInterface $input, OutputInterface $output )
    {
        $output->setDecorated( true );
        $input->setInteractive( true );

        $this->contentClassIdentifier = $input->getArgument( 'contentClassIdentifier' );

        $this->repository     = $this->getContainer()->get( 'ezpublish.api.repository' );
        $this->searchService  = $this->repository->getSearchService();
        $this->contentService = $this->repository->getContentService();

        // Load and set Administrator User for permissions
        $administratorUser = $this->repository->getUserService()->loadUser( 14 );
        $this->repository->getPermissionResolver()->setCurrentUserReference( $administratorUser );

        // Count number of contents to update
        $this->totalCount = $this->fetchCount();
    }

    /**
     * Returns number of contents who will be updated
     *
     * @return mixed
     */
    private function fetchCount()
    {
        $this->searchService = $this->repository->getSearchService();

        // Prepare count
        $query = new LocationQuery();

        $query->query        = new Criterion\LogicalAnd( [
                                                             new Criterion\ContentTypeIdentifier( $this->contentClassIdentifier ),
                                                         ] );
        $query->performCount = true;
        $query->limit        = 0;
        $results             = $this->searchService->findContent( $query );

        return $results->totalCount;
    }

    protected function configure()
    {
        $this->setName( 'sqli:object:republish' )
            ->setDescription( 'Publish all contents of the ContentType with specified identifier' )
            ->addArgument( 'contentClassIdentifier', InputArgument::REQUIRED, "ContentType identifier" );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $output->writeln( "Fetching all objects of contentType '<comment>$this->contentClassIdentifier</comment>'" );

        // Informations
        $output->writeln( "<comment>{$this->totalCount}</comment> contents found" );

        // Ask confirmation
        $output->writeln( "" );
        $helper   = $this->getHelper( 'question' );
        $question = new ConfirmationQuestion(
            '<question>Are you sure you want to proceed [y/N]?</question> ',
            false
        );

        if( !$helper->ask( $input, $output, $question ) )
        {
            $output->writeln( '' );

            return;
        }

        $output->writeln( "" );
        $output->writeln( "Starting job :" );

        $offset = 0;
        do
        {
            // Fetch small group of contents
            $items = $this->fetch( self::FETCH_LIMIT, $offset );

            // Publish each content
            foreach( $items as $index => $content )
            {
                /** @var $content Content */
                $contentDraft = $this->contentService->createContentDraft( $content->getVersionInfo()->getContentInfo() );
                $this->contentService->publishVersion( $contentDraft->getVersionInfo() );

                $output->writeln( sprintf( "[%s/%s] contentID: %s <comment>%s</comment> published", ( $offset + $index + 1 ), $this->totalCount, $content->id, $content->getName() ) );
            }

            $offset += self::FETCH_LIMIT;
        } while( $offset < $this->totalCount );

        $output->writeln( "" );
        $output->writeln( "<info>Job finished !</info>" );
    }

    /**
     * Fetch contents with offset and limit
     *
     * @param     $limit
     * @param int $offset
     * @return array
     */
    private function fetch( $limit, $offset = 0 )
    {
        $this->searchService = $this->repository->getSearchService();

        // Prepare fetch with offset and limit
        $query = new LocationQuery();

        $query->query        = new Criterion\LogicalAnd( [
                                                             new Criterion\ContentTypeIdentifier( $this->contentClassIdentifier ),
                                                         ] );
        $query->performCount = true;
        $query->limit        = $limit;
        $query->offset       = $offset;
        $results             = $this->searchService->findContent( $query );
        $items               = [];

        // Prepare an array with contents
        foreach( $results->searchHits as $item )
        {
            $items[] = $item->valueObject;
        }

        return $items;
    }
}
<?php

namespace SQLI\EzToolboxBundle\Command;

use eZ\Publish\API\Repository\LocationService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LocationUnhideCommand extends ContainerAwareCommand
{
    /** @var LocationService */
    private $locationService;

    public function initialize( InputInterface $input, OutputInterface $output )
    {
        $output->setDecorated( true );

        $repository            = $this->getContainer()->get( 'ezpublish.api.repository' );
        $this->locationService = $this->getContainer()->get( 'ezpublish.api.service.inner_location' );

        // Load and set Administrator User
        $administratorUser = $repository->getUserService()->loadUser( 14 );
        $repository->getPermissionResolver()->setCurrentUserReference( $administratorUser );
    }

    protected function configure()
    {
        $this->setName( 'sqli:object:unhide' )
            ->setDescription( 'Unhide location' )
            ->addArgument( 'location', InputArgument::REQUIRED, "LocationID to unhide" );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        if( $locationID = $input->getArgument( 'location' ) )
        {
            $output->write( "Unhide locationID $locationID : " );

            $location    = $this->locationService->loadLocation( $locationID );
            $contentName = $location->getContent()->getName();
            $this->locationService->unhideLocation( $location );
            $output->writeln( "<info>" . $contentName . "</info>" );
        }
    }
}
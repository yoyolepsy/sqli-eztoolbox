<?php

namespace SQLI\EzToolboxBundle\Menu;

use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use SQLI\EzToolboxBundle\Services\TabEntityHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuListener implements EventSubscriberInterface
{
    const SQLI_ADMIN_MENU_ROOT                  = "sqli_admin__menu_root";
    const SQLI_ADMIN_MENU_ENTITIES_TAB_PREFIX   = "sqli_admin__menu_entities_tab__";
    const SQLI_ADMIN_MENU_CONTENTTYPE_INSTALLER = "sqli_admin__menu_contenttypes_installer";
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;
    /** @var TabEntityHelper */
    private $tabEntityHelper;

    public function __construct( AuthorizationCheckerInterface $authorizationChecker, TabEntityHelper $tabEntityHelper )
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->tabEntityHelper      = $tabEntityHelper;
    }

    public static function getSubscribedEvents()
    {
        return array( ConfigureMenuEvent::MAIN_MENU => 'onMainMenuBuild' );
    }

    public function onMainMenuBuild( ConfigureMenuEvent $event )
    {
        $menu = $event->getMenu();

        $menu->addChild(
            self::SQLI_ADMIN_MENU_ROOT,
            [
                'label' => self::SQLI_ADMIN_MENU_ROOT,
            ]
        )->setExtra('translation_domain', 'sqli_admin' );

        // SQLI Entity Manager
        if( $this->authorizationChecker->isGranted( 'ez:sqli_admin:list_entities' ) )
        {
            // Read "tabname" entity's annotations to generate submenu items
            $tabClasses = $this->tabEntityHelper->entitiesGroupedByTab();
            foreach( array_keys( $tabClasses ) as $tabname )
            {
                $menu[self::SQLI_ADMIN_MENU_ROOT]->addChild(
                    self::SQLI_ADMIN_MENU_ENTITIES_TAB_PREFIX . $tabname,
                    [
                        'label'              => self::SQLI_ADMIN_MENU_ENTITIES_TAB_PREFIX . $tabname,
                        'route'              => 'sqli_eztoolbox_entitymanager_homepage',
                        'routeParameters'    => [ 'tabname' => $tabname ],
                    ]
                )->setExtra('translation_domain', 'sqli_admin' );
            }
        }

        // SQLI ContentType Installer
        if( $this->authorizationChecker->isGranted( 'ez:sqli_admin:content_type_installer' ) )
        {
            $menu[self::SQLI_ADMIN_MENU_ROOT]->addChild(
                self::SQLI_ADMIN_MENU_CONTENTTYPE_INSTALLER,
                [
                    'label'              => self::SQLI_ADMIN_MENU_CONTENTTYPE_INSTALLER,
                    'route'              => 'sqli_eztoolbox_contenttype_installer_list',
                ]
            )->setExtra('translation_domain', 'sqli_admin' );
        }
    }
}
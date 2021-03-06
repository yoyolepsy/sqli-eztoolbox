<?php

namespace SQLI\EzToolboxBundle\Services;

use SQLI\EzToolboxBundle\Classes\Filter;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FilterEntityHelper
{
    const SESSION_VARNAME = "sqli_admin_filter_fqcn";

    /** @var SessionInterface */
    private $session;

    public function __construct( SessionInterface $session )
    {
        $this->session = $session;
    }

    /**
     * Save Filter object in session for specified FQCN
     *
     * @param string $fqcn
     * @param Filter $filter
     */
    public function setFilter( $fqcn, Filter $filter )
    {
        // Set in session
        $filters = $this->session->get( self::SESSION_VARNAME, [] );
        $filters[$fqcn] = $filter;
        $this->session->set( self::SESSION_VARNAME, $filters );
    }

    /**
     * Get Filter object from session for specified FQCN
     *
     * @param $fqcn
     * @return Filter|null
     */
    public function getFilter( $fqcn ): ?Filter
    {
        // Get from session
        $filters = $this->session->get( self::SESSION_VARNAME, [] );

        return array_key_exists( $fqcn, $filters ) ? $filters[$fqcn] : null;
    }

    public function resetFilter( $fqcn )
    {
        $filters = $this->session->get( self::SESSION_VARNAME, [] );
        unset( $filters[$fqcn] );

        $this->session->set( self::SESSION_VARNAME, $filters );
    }
}
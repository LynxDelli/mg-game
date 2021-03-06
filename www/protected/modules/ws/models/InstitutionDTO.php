<?php
/**
 * @package
 */
class InstitutionDTO {
    /**
     * @var string
     * @soap
     */
    public $username;
    /**
     * @var string
     * @soap
     */
    public $email;
    /**
     * @var string
     * @soap
     */
    public $password;
    /**
     * @var string
     * @soap
     */
    public $name;
    /**
     * @var string
     * @soap
     */
    public $url;
    /**
     * @var string
     * @soap
     */
    public $logoUrl;
    /**
     * @var string
     * @soap
     */
    public $description;

    /**
     * @var string
     * @soap
     */
    public $token;

    /**
     * @var string
     * @soap
     */
    public $ip;

    /**
     * @var string
     * @soap
     */
    public $website;
}
<?php
/**
 * @package FishPig_WordPress_Yoast
 * @author  Ben Tideswell (ben@fishpig.com)
 * @url     https://fishpig.co.uk/magento/wordpress-integration/yoast/
 */
declare(strict_types=1);

namespace FishPig\WordPress_Yoast\Plugin\FishPig_WordPress\Model;

use FishPig\WordPress\Model\Term;

class TermPlugin
{
    /**
     * @auto
     */
    protected $config = null;

    /**
     * @auto
     */
    protected $seoMetaDataProvider = null;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @param \FishPig\WordPress_Yoast\Model\Config $config
     * @param \FishPig\WordPress_Yoast\Controller\Term\View\SeoMetaDataProvider $seoMetaDataProvider
     */
    public function __construct(
        \FishPig\WordPress_Yoast\Model\Config $config,
        \FishPig\WordPress_Yoast\Controller\Term\View\SeoMetaDataProvider $seoMetaDataProvider
    ) {
        $this->config = $config;
        $this->seoMetaDataProvider = $seoMetaDataProvider;
    }

    /**
     * Exclude terms set to noindex in Yoast (eg. from the XML sitemap)
     *
     * @param  Term $subject
     * @param  bool $result
     * @return bool
     */
    public function afterIsPublic(Term $subject, bool $result): bool
    {
        if (!$result || !$subject->getId() || !$this->config->isEnabled()) {
            return $result;
        }

        $key = $subject->getTaxonomy() . ':' . $subject->getId();

        if (!isset($this->cache[$key])) {
            $robots = $this->seoMetaDataProvider->getRobots($subject);
            $this->cache[$key] = strtolower($robots['index'] ?? '') !== 'noindex';
        }

        return $this->cache[$key];
    }
}

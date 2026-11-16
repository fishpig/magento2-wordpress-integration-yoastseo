<?php
/**
 * @package FishPig_WordPress_Yoast
 * @author  Ben Tideswell (ben@fishpig.com)
 * @url     https://fishpig.co.uk/magento/wordpress-integration/yoast/
 */
declare(strict_types=1);

namespace FishPig\WordPress_Yoast\Controller\Term\View;

class SeoMetaDataProvider extends \FishPig\WordPress\Controller\Term\View\SeoMetaDataProvider
{
    /**
     * Keys used by Yoast in the wpseo_taxonomy_meta option
     */
    const FIELD_NOINDEX = 'wpseo_noindex';
    const FIELD_CANONICAL = 'wpseo_canonical';

    /**
     * @auto
     */
    protected $config = null;

    /**
     * @auto
     */
    protected $stringRewriter = null;

    /**
     * @auto
     */
    protected $optionRepository = null;

    /**
     * @var array
     */
    private $taxonomyMeta = null;

    /**
     * @param \FishPig\WordPress\Helper\BlogInfo $blogInfo
     */
    public function __construct(
        \FishPig\WordPress\Helper\BlogInfo $blogInfo,
        \FishPig\WordPress_Yoast\Model\Config $config,
        \FishPig\WordPress_Yoast\Model\StringRewriter $stringRewriter,
        \FishPig\WordPress\Model\OptionRepository $optionRepository
    ) {
        $this->config = $config;
        $this->stringRewriter = $stringRewriter;
        $this->optionRepository = $optionRepository;
        parent::__construct($blogInfo);
    }

    /**
     * @param  \Magento\Framework\View\Result\Page $resultPage,
     * @param  \FishPig\WordPress\Api\Data\ViewableModelInterface $object
     * @return void
     */
    public function addMetaData(
        \Magento\Framework\View\Result\Page $resultPage,
        \FishPig\WordPress\Api\Data\ViewableModelInterface $term
    ): void {
        parent::addMetaData($resultPage, $term);

        if (!$this->config->isEnabled()) {
            return;
        }

        // Meta Title
        if ($metaTitle = $this->stringRewriter->rewrite(
            $this->getMetaValue($term, 'wpseo_title') ?: $this->config->getTitleFormat('tax_' . $term->getTaxonomy()),
            $term
        )) {
            $this->setMetaTitle($metaTitle);
        }

        // Meta Description
        $metaDescFormat = $this->getMetaValue($term, 'wpseo_desc')
            ?: $this->config->getMetaDescriptionFormat('tax_' . $term->getTaxonomy());

        if ($metaDesc = $this->stringRewriter->rewrite($metaDescFormat, $term)) {
            $this->setMetaDescription($metaDesc);
        }

        // Canonical
        $this->setCanonicalUrl(
            $this->getMetaValue($term, self::FIELD_CANONICAL) ?: $term->getUrl()
        );

        // Robots
        if ($robots = $this->getRobots($term)) {
            $this->setRobots(implode(',', $robots));
        }
    }

    /**
     * @param  \FishPig\WordPress\Model\Term $term
     * @return array
     */
    public function getRobots(\FishPig\WordPress\Model\Term $term): array
    {
        if (!$this->getBlogInfo()->isBlogPublic()) {
            return ['index' => 'noindex', 'follow' => 'nofollow'];
        }

        $robots = ['index' => 'index', 'follow' => 'follow'];

        if ($this->config->isTypeNoindex('tax_' . $term->getTaxonomy())) {
            $robots['index'] = 'noindex';
        }

        // Yoast stores 'default', 'index' or 'noindex'. 'default' uses the taxonomy setting.
        $value = trim((string)$this->getMetaValue($term, self::FIELD_NOINDEX));

        if (in_array($value, ['index', 'noindex'], true)) {
            $robots['index'] = $value;
        }

        return $robots;
    }

    /**
     * @param  \FishPig\WordPress\Model\Term $term
     * @param  string $key
     * @return string
     */
    private function getMetaValue(\FishPig\WordPress\Model\Term $term, string $key)
    {
        if ($this->taxonomyMeta === null) {
            $this->taxonomyMeta = $this->optionRepository->getUnserialized('wpseo_taxonomy_meta');
        }

        return isset($this->taxonomyMeta[$term->getTaxonomy()][$term->getId()][$key])
            ? $this->taxonomyMeta[$term->getTaxonomy()][$term->getId()][$key]
            : '';
    }
}

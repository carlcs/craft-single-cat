<?php
/**
 * Single Cat plugin for Craft CMS 3.x
 *
 * Fieldtype that allows the user to select a single category from a drop-down.
 *
 * @link      https://elivz.com
 * @copyright Copyright (c) 2018 Eli Van Zoeren
 */

namespace elivz\singlecat\fields;

use elivz\singlecat\SingleCat;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\Category;
use craft\fields\BaseRelationField;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use yii\db\Schema;
use craft\helpers\Json;

/**
 * @author  Eli Van Zoeren
 * @package SingleCat
 * @since   1.0.0
 */
class SingleCatField extends BaseRelationField
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('single-cat', 'Single Category');
    }

    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return Category::class;
    }

    // Properties
    // =========================================================================

    /**
     * @var int|null Branch limit
     */
    public $branchLimit = 1;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->allowLimit = false;
        $this->allowMultipleSources = false;
        $this->settingsTemplate = 'single-cat/_components/fields/settings';
        $this->inputTemplate = 'single-cat/_components/fields/input';
        $this->inputJsClass = false;
        $this->sortable = false;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if (is_array($value)) {
            /**
             * @var Category[] $categories 
             */
            $categories = Category::find()
                ->siteId($this->targetSiteId($element))
                ->id(array_values(array_filter($value)))
                ->anyStatus()
                ->all();
                
                // Enforce the branch limit
                $categoriesService = Craft::$app->getCategories();
                $categoriesService->applyBranchLimitToCategories($categories, $this->branchLimit);

            $value = ArrayHelper::getColumn($categories, 'id');
        }

        return parent::normalizeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        // Make sure the field is set to a valid category group
        if ($this->source) {
            $source = ElementHelper::findSource(static::elementType(), $this->source, 'field');
        }

        if (empty($source)) {
            return '<p class="error">' . Craft::t('app', 'This field is not set to a valid category group.') . '</p>';
        }

        // Get all the categories in this group
        $categories = Category::find()->groupId($source['criteria']['groupId'])->all();

        // Render the input template
        return Craft::$app->getView()->renderTemplate(
            $this->inputTemplate,
            [
                'name' => $this->handle,
                'value' => $value->one(),
                'field' => $this,
                'source' => $source,
                'categories' => $categories,
            ]
        );
    }
}

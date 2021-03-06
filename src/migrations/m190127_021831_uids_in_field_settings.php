<?php

namespace elivz\singlecat\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use elivz\singlecat\fields\SingleCatField;

/**
 * m190127_021831_uids_in_field_settings migration.
 */
class m190127_021831_uids_in_field_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from([Table::FIELDS])
            ->where(['type' => SingleCatField::class])
            ->all();

        $sites = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::SITES])
            ->pairs();

        $categoryGroups = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::CATEGORYGROUPS])
            ->pairs();

        foreach ($fields as $field) {
            if ($field['settings']) {
                $settings = Json::decodeIfJson($field['settings']) ?: [];
            } else {
                $settings = [];
            }

            if (array_key_exists('targetSiteId', $settings)) {
                $settings['targetSiteId'] = $sites[$settings['targetSiteId']] ?? null;
            }

            if (!empty($settings['source']) && strpos($settings['source'], ':') !== false) {
                $source = explode(':', $settings['source']);
                $settings['source'] = $source[0] . ':' . ($categoryGroups[$source[1]] ?? $source[1]);
            }

            $settings = Json::encode($settings);

            $this->update(Table::FIELDS, ['settings' => $settings], ['id' => $field['id']], [], false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190127_021831_uids_in_field_settings cannot be reverted.\n";
        return false;
    }
}

<?php

namespace Sultanov\Efusion\Entity;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

class CustomGridTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_custom_deal_grid';
	}

	public static function getMap(): array
	{
		return [
            (new IntegerField('ID'))
                    ->configurePrimary(true)
                    ->configureAutocomplete(true),

            (new IntegerField('DEAL_ID'))
                    ->configureRequired(true),

            (new StringField('TITLE'))
                    ->configureRequired(true)
                    ->configureSize(255),

            (new StringField('DESCRIPTION'))
                    ->configureSize(1000),

            (new DatetimeField('CREATED_AT'))
                    ->configureDefaultValue(function ()
                    {
                        return new DateTime();
                    }),

            (new DatetimeField('UPDATED_AT')),
		];
	}
}
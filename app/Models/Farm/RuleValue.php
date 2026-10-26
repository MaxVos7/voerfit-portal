<?php

namespace App\Models\Farm;

use Database\Factories\Farm\RuleValueFactory;
use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One feeding rule setting, published by the prediction and feed engine jobs (read-only here).
 *
 * The value is the text the source wrote, with a decimal comma where it has decimals.
 *
 * @property string $name
 * @property string $value
 * @property string|null $source
 */
#[Connection('farm')]
#[Table(name: 'voerfit_rule_values', key: 'name', keyType: 'string', incrementing: false, timestamps: false)]
class RuleValue extends Model
{
    /** @use HasFactory<RuleValueFactory> */
    use HasFactory;
}

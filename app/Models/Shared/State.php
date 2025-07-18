<?php

declare(strict_types=1);

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $code
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State query()
 *
 * @mixin \Eloquent
 */
class State extends Model
{
    use Sushi;

    /** @var array<int, array<string, string>> */
    public array $rows = [
        ['name' => 'Alabama', 'code' => 'AL'],
        ['name' => 'Alaska', 'code' => 'AK'],
        ['name' => 'Arizona', 'code' => 'AZ'],
        ['name' => 'Arkansas', 'code' => 'AR'],
        ['name' => 'California', 'code' => 'CA'],
        ['name' => 'Colorado', 'code' => 'CO'],
        ['name' => 'Connecticut', 'code' => 'CT'],
        ['name' => 'Delaware', 'code' => 'DE'],
        ['name' => 'District of Columbia', 'code' => 'DC'],
        ['name' => 'Florida', 'code' => 'FL'],
        ['name' => 'Georgia', 'code' => 'GA'],
        ['name' => 'Hawaii', 'code' => 'HI'],
        ['name' => 'Idaho', 'code' => 'ID'],
        ['name' => 'Illinois', 'code' => 'IL'],
        ['name' => 'Indiana', 'code' => 'IN'],
        ['name' => 'Iowa', 'code' => 'IA'],
        ['name' => 'Kansas', 'code' => 'KS'],
        ['name' => 'Kentucky', 'code' => 'KY'],
        ['name' => 'Louisiana', 'code' => 'LA'],
        ['name' => 'Maine', 'code' => 'ME'],
        ['name' => 'Maryland', 'code' => 'MD'],
        ['name' => 'Massachusetts', 'code' => 'MA'],
        ['name' => 'Michigan', 'code' => 'MI'],
        ['name' => 'Minnesota', 'code' => 'MN'],
        ['name' => 'Mississippi', 'code' => 'MS'],
        ['name' => 'Missouri', 'code' => 'MO'],
        ['name' => 'Montana', 'code' => 'MT'],
        ['name' => 'Nebraska', 'code' => 'NE'],
        ['name' => 'Nevada', 'code' => 'NV'],
        ['name' => 'New Hampshire', 'code' => 'NH'],
        ['name' => 'New Jersey', 'code' => 'NJ'],
        ['name' => 'New Mexico', 'code' => 'NM'],
        ['name' => 'New York', 'code' => 'NY'],
        ['name' => 'North Carolina', 'code' => 'NC'],
        ['name' => 'North Dakota', 'code' => 'ND'],
        ['name' => 'Ohio', 'code' => 'OH'],
        ['name' => 'Oklahoma', 'code' => 'OK'],
        ['name' => 'Oregon', 'code' => 'OR'],
        ['name' => 'Pennsylvania', 'code' => 'PA'],
        ['name' => 'Rhode Island', 'code' => 'RI'],
        ['name' => 'South Carolina', 'code' => 'SC'],
        ['name' => 'South Dakota', 'code' => 'SD'],
        ['name' => 'Tennessee', 'code' => 'TN'],
        ['name' => 'Texas', 'code' => 'TX'],
        ['name' => 'Utah', 'code' => 'UT'],
        ['name' => 'Vermont', 'code' => 'VT'],
        ['name' => 'Virginia', 'code' => 'VA'],
        ['name' => 'Washington', 'code' => 'WA'],
        ['name' => 'West Virginia', 'code' => 'WV'],
        ['name' => 'Wisconsin', 'code' => 'WI'],
        ['name' => 'Wyoming', 'code' => 'WY'],
    ];
}

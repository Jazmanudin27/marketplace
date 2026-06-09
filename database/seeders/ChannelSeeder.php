<?php

namespace Database\Seeders;

use App\Models\Channel;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            [
                'code'     => 'shopee',
                'name'     => 'Shopee',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fe/Shopee.svg/512px-Shopee.svg.png',
                'status'   => true,
            ],
            [
                'code'     => 'tokopedia',
                'name'     => 'Tokopedia',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/73/Tokopedia_logo.svg/512px-Tokopedia_logo.svg.png',
                'status'   => true,
            ],
            [
                'code'     => 'tiktok',
                'name'     => 'TikTok Shop',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a9/TikTok_logo.svg',
                'status'   => true,
            ],
            [
                'code'     => 'lazada',
                'name'     => 'Lazada',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Lazada_Logo.svg/512px-Lazada_Logo.svg.png',
                'status'   => true,
            ],
            [
                'code'     => 'blibli',
                'name'     => 'Blibli',
                'logo_url' => null,
                'status'   => true,
            ],
        ];

        foreach ($channels as $channel) {
            Channel::updateOrCreate(['code' => $channel['code']], $channel);
        }
    }
}

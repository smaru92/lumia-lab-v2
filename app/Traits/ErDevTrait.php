<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait ErDevTrait
{
    public $tierRange = [
        [
            'tier' => 'All',
            'tierNumber' => null,
        ],
        [
            'tier' => 'Platinum',
            'tierNumber' => null,
        ],
        [
            'tier' => 'Diamond',
            'tierNumber' => null,
        ],
        [
            'tier' => 'Diamond',
            'tierNumber' => 2,
        ],
        [
            'tier' => 'Meteorite',
            'tierNumber' => null,
        ],
        [
            'tier' => 'Mithril',
            'tierNumber' => 'low',
        ],
        [
            'tier' => 'Mithril',
            'tierNumber' => 'high',
        ],
        [
            'tier' => 'Top',
            'tierNumber' => null,
        ],
    ];
    // 테이블의 컬럼 목록을 가져온다.
    public function getColumns($tableName)
    {
        return Schema::getColumnListing($tableName);
    }



    /**
     * 무기명 특정 언어로 변경, 가짓수가 적어서 하드코딩
     * 기본적으로 한글 무기로 변경
     * @param string $weaponType
     * @param string $lang
     * @return string
     */
    public function replaceWeaponType(string $weaponType, string $lang = 'ko')
    {
       $weapons = [
            [
                'ko' => '아르카나',
                'en' => 'Arcana'
            ],
            [
                'ko' => '돌격소총',
                'en' => 'AssaultRifle'
            ],
            [
                'ko' => '도끼',
                'en' => 'Axe'
            ],
            [
                'ko' => '방망이',
                'en' => 'Bat'
            ],
            [
                'ko' => '활',
                'en' => 'Bow'
            ],
            [
                'ko' => '카메라',
                'en' => 'Camera'
            ],
            [
                'ko' => '석궁',
                'en' => 'CrossBow'
            ],
            [
                'ko' => '암기',
                'en' => 'DirectFire'
            ],
            [
                'ko' => '쌍검',
                'en' => 'DualSword'
            ],
            [
                'ko' => '글러브',
                'en' => 'Glove'
            ],
            [
                'ko' => '기타',
                'en' => 'Guitar'
            ],
            [
                'ko' => '망치',
                'en' => 'Hammer'
            ],
            [
                'ko' => '투척무기',
                'en' => 'HighAngleFire'
            ],
            [
                'ko' => '쌍절곤',
                'en' => 'Nunchaku'
            ],
            [
                'ko' => '단검',
                'en' => 'OneHandSword'
            ],
            [
                'ko' => '권총',
                'en' => 'Pistol'
            ],
            [
                'ko' => '레이피어',
                'en' => 'Rapier'
            ],
            [
                'ko' => '저격총',
                'en' => 'SniperRifle'
            ],
            [
                'ko' => '창',
                'en' => 'Spear'
            ],
            [
                'ko' => '톤파',
                'en' => 'Tonfa'
            ],
            [
                'ko' => '양손검',
                'en' => 'TwoHandSword'
            ],
            [
                'ko' => 'VF의수',
                'en' => 'VFArm'
            ],
            [
                'ko' => '채찍',
                'en' => 'Whip'
            ],
            [
                'ko' => '데스에더',
                'en' => 'DeathAdder'
            ],
            [
                'ko' => '블랙맘바',
                'en' => 'BlackMamba'
            ],
            [
                'ko' => '사이드와인더',
                'en' => 'SideWinder'
            ],
       ];

       foreach ($weapons as $weapon) {
           if ($weapon['en'] == $weaponType || $weapon['ko'] == $weaponType) {
               return $weapon[$lang];
           }
       }

       return $weaponType;
    }

    /**
     * 아이템 등급명 특정 언어로 변경, 가짓수가 적어서 하드코딩
     * 기본적으로 한글 등급으로 변경
     * @param string $itemGrade
     * @param string $lang
     * @return string
     */
    public function replaceItemGrade(string $itemGrade, string $lang = 'ko')
    {
        $grades = [
            [
                'ko' => '일반',
                'en' => 'Common'
            ],
            [
                'ko' => '고급',
                'en' => 'Uncommon'
            ],
            [
                'ko' => '희귀',
                'en' => 'Rare'
            ],
            [
                'ko' => '영웅',
                'en' => 'Epic'
            ],
            [
                'ko' => '전설',
                'en' => 'Legend'
            ],
            [
                'ko' => '초월',
                'en' => 'Mythic'
            ],
        ];

        foreach ($grades as $grade) {
            if ($grade['en'] == $itemGrade || $grade['ko'] == $itemGrade) {
                return $grade[$lang];
            }
        }

        return $itemGrade;
    }

    /**
     * 아이템 부위위명 특정 언어로 변경, 가짓수가 적어서 하드코딩
     * 기본적으로 한글 등급으로 변경
     * @param string $itemGrade
     * @param string $lang
     * @return string
     */
    public function replaceItemType2(string $itemType2, string $lang = 'ko')
    {
        $grades = [
            [
                'ko' => '무기',
                'en' => 'Weapon'
            ],
            [
                'ko' => '머리',
                'en' => 'Head'
            ],
            [
                'ko' => '옷',
                'en' => 'Chest'
            ],
            [
                'ko' => '팔',
                'en' => 'Arm'
            ],
            [
                'ko' => '다리',
                'en' => 'Leg'
            ],
        ];

        foreach ($grades as $grade) {
            if ($grade['en'] == $itemType2 || $grade['ko'] == $itemType2) {
                return $grade[$lang];
            }
        }

        return $itemType2;
    }

    /**
     * 티어명 특정 언어로 변경, 가짓수가 적어서 하드코딩
     * 기본적으로 한글 티어로 변경
     * @param string $tierName
     * @param string $lang
     * @return string
     */
    public function replaceTierName(string $tierName, string $lang = 'ko')
    {
        $tiers = [
            [
                'ko' => '전체',
                'en' => 'All'
            ],
            [
                'ko' => '플레티넘',
                'en' => 'Platinum'
            ],
            [
                'ko' => '다이아',
                'en' => 'Diamond'
            ],
            [
                'ko' => '다이아2',
                'en' => 'Diamond2'
            ],
            [
                'ko' => '메테오라이트',
                'en' => 'Meteorite'
            ],
            [
                'ko' => '미스릴',
                'en' => 'Mithril'
            ],
            [
                'ko' => '미스릴',
                'en' => 'Mithrillow'
            ],
            [
                'ko' => '미스릴(8000+)',
                'en' => 'Mithrilhigh'
            ],
            [
                'ko' => '최상위큐',
                'en' => 'Top'
            ],
        ];

        foreach ($tiers as $tier) {
            if ($tier['en'] == $tierName || $tier['ko'] == $tierName) {
                return $tier[$lang];
            }
        }

        return $tierName;
    }
}

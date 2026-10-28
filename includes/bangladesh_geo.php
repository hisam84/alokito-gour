<?php
/**
 * Bangladesh Administrative Geographic Hierarchy (Tree Format)
 * Divisions -> Districts -> Upazilas
 * Alokito Gour Clone
 */

declare(strict_types=1);

function get_bangladesh_geo(): array {
    static $geo = null;
    if ($geo !== null) {
        return $geo;
    }

    $geo = [
        'রাজশাহী' => [
            'চাঁপাইনবাবগঞ্জ' => [
                'চাঁপাইনবাবগঞ্জ সদর',
                'শিবগঞ্জ',
                'নাচোল',
                'গোমস্তাপুর',
                'ভোলাহাট'
            ],
            'রাজশাহী' => [
                'বোয়ালিয়া',
                'মতিহার',
                'রাজপাড়া',
                'চন্দ্রিমা',
                'পবা',
                'বাঘা',
                'চারঘাট',
                'দুর্গাপুর',
                'পুঠিয়া',
                'বাগমারা',
                'তানোর',
                'মোহনপুর',
                'গোদাগাড়ী'
            ],
            'বগুড়া' => [
                'বগুড়া সদর',
                'শিবগঞ্জ',
                'সোনাতলা',
                'গাবতলী',
                'শাজাহানপুর',
                'শেরপুর',
                'ধুনট',
                'সারিয়াকান্দি',
                'নন্দীগ্রাম',
                'আদমদীঘি',
                'দুপচাঁচিয়া',
                'কাহালু'
            ],
            'নওগাঁ' => [
                'নওগাঁ সদর',
                'মহাদেবপুর',
                'পত্নীতলা',
                'ধামইরহাট',
                'নিয়ামতপুর',
                'মান্দা',
                'আত্রাই',
                'রাণীনগর',
                'বদলগাছী',
                'পোরশা',
                'সাপাহার'
            ],
            'নাটোর' => [
                'নাটোর সদর',
                'সিংড়া',
                'বড়াইগ্রাম',
                'বাগাতিপাড়া',
                'লালপুর',
                'গুরুদাসপুর',
                'নলডাঙ্গা'
            ],
            'পাবনা' => [
                'পাবনা সদর',
                'ঈশ্বরদী',
                'আটঘরিয়া',
                'চাটমোহর',
                'ভাঙ্গুড়া',
                'ফরিদপুর',
                'সাঁথিয়া',
                'বেড়া',
                'সুজানগর'
            ],
            'সিরাজগঞ্জ' => [
                'সিরাজগঞ্জ সদর',
                'শাহজাদপুর',
                'উল্লাপাড়া',
                'বেলকুচি',
                'তাড়াশ',
                'রায়গঞ্জ',
                'চৌহালী',
                'কামারখন্দ',
                'কাজিপুর'
            ],
            'জয়পুরহাট' => [
                'জয়পুরহাট সদর',
                'পাঁচবিবি',
                'কালাই',
                'ক্ষেতলাল',
                'আক্কেলপুর'
            ]
        ],
        'ঢাকা' => [
            'ঢাকা' => [
                'ধানমন্ডি',
                'গুলশান',
                'মিরপুর',
                'উত্তরা',
                'মতিঝিল',
                'সাভার',
                'ধামরাই',
                'কেরানীগঞ্জ',
                'দোহার',
                'নবাবগঞ্জ'
            ],
            'গাজীপুর' => [
                'গাজীপুর সদর',
                'কালিয়াকৈর',
                'শ্রীপুর',
                'কাপাসিয়া',
                'কালীগঞ্জ'
            ],
            'নারায়ণগঞ্জ' => [
                'নারায়ণগঞ্জ সদর',
                'রূপগঞ্জ',
                'সোনারগাঁও',
                'বন্দর',
                'আড়াইহাজার'
            ],
            'নরসিংদী' => [
                'নরসিংদী সদর',
                'বেলাবো',
                'মনোহরদী',
                'পলাশ',
                'রায়পুরা',
                'শিবপুর'
            ],
            'মানিকগঞ্জ' => [
                'মানিকগঞ্জ সদর',
                'সাটুরিয়া',
                'শিবালয়',
                'সিঙ্গাইর',
                'হরিরামপুর',
                'দৌলতপুর',
                'ঘিওর'
            ],
            'মুন্সীগঞ্জ' => [
                'মুন্সীগঞ্জ সদর',
                'শ্রীনগর',
                'সিরাজদিখান',
                'লৌহজং',
                'টঙ্গীবাড়ী',
                'গজারিয়া'
            ],
            'ফরিদপুর' => [
                'ফরিদপুর সদর',
                'বোয়ালমারী',
                'মধুখালী',
                'ভাঙ্গা',
                'আলফাডাঙ্গা',
                'নগরকান্দা',
                'চরভদ্রাসন',
                'সদরপুর',
                'সালথা'
            ],
            'মাদারীপুর' => [
                'মাদারীপুর সদর',
                'শিবচর',
                'কালকিনি',
                'রাজৈর',
                'ডাসার'
            ],
            'শরিয়তপুর' => [
                'শরিয়তপুর সদর',
                'জাজিরা',
                'নড়িয়া',
                'ডামুড্যা',
                'ভেদরগঞ্জ',
                'গোসাইরহাট'
            ],
            'গোপালগঞ্জ' => [
                'গোপালগঞ্জ সদর',
                'টুঙ্গিপাড়া',
                'কোটালীপাড়া',
                'কাশিয়ানী',
                'মুকসুদপুর'
            ],
            'রাজবাড়ী' => [
                'রাজবাড়ী সদর',
                'পাংশা',
                'কালুখালী',
                'বালিয়াকান্দি',
                'গোয়ালন্দ'
            ],
            'টাঙ্গাইল' => [
                'টাঙ্গাইল সদর',
                'মির্জাপুর',
                'দেলদুয়ার',
                'নাগরপুর',
                'সখীপুর',
                'বাসাইল',
                'ঘাটাইল',
                'মধুপুর',
                'ধনবাড়ী',
                'ভূঞাপুর',
                'গোপালপুর',
                'কালিহাতী'
            ],
            'কিশোরগঞ্জ' => [
                'কিশোরগঞ্জ সদর',
                'ভৈরব',
                'কটিয়াদী',
                'কুলিয়ারচর',
                'তাড়াইল',
                'বাজিতপুর',
                'হোসেনপুর',
                'পাকুন্দিয়া',
                'অষ্টগ্রাম',
                'ইটনা',
                'করিমগঞ্জ',
                'নিকলী',
                'মিঠামইন'
            ]
        ],
        'চট্টগ্রাম' => [
            'চট্টগ্রাম' => [
                'কোতোয়ালী',
                'পাঁচলাইশ',
                'ডবলমুরিং',
                'পতেঙ্গা',
                'সীতাকুণ্ড',
                'মীরসরাই',
                'সন্দ্বীপ',
                'ফটিকছড়ি',
                'হাটহাজারী',
                'রাউজান',
                'রাঙ্গুনিয়া',
                'পটিয়া',
                'আনোয়ারা',
                'বোয়ালখালী',
                'চন্দনাইশ',
                'সাতকানিয়া',
                'লোহাগাড়া',
                'কর্ণফুলী',
                'বাঁশখালী'
            ],
            'কক্সবাজার' => [
                'কক্সবাজার সদর',
                'টেকনাফ',
                'উখিয়া',
                'চকরিয়া',
                'পেকুয়া',
                'রামু',
                'কুতুবদিয়া',
                'মহেশখালী',
                'ঈদগাঁও'
            ],
            'কুমিল্লা' => [
                'কুমিল্লা আদর্শ সদর',
                'কুমিল্লা সদর দক্ষিণ',
                'লাকসাম',
                'বরুড়া',
                'চান্দিনা',
                'দাউদকান্দি',
                'চৌদ্দগ্রাম',
                'ব্রাহ্মণপাড়া',
                'বুড়িচং',
                'দেবিদ্বার',
                'হোমনা',
                'মুরাদনগর',
                'মেঘনা',
                'তিতাস',
                'মনোহরগঞ্জ',
                'লালমাই'
            ],
            'ফেনী' => [
                'ফেনী সদর',
                'দাগনভূঞা',
                'সোনাগাজী',
                'ছাগলনাইয়া',
                'পরশুরাম',
                'ফুলগাজী'
            ],
            'ব্রাহ্মণবাড়িয়া' => [
                'ব্রাহ্মণবাড়িয়া সদর',
                'আশুগঞ্জ',
                'সরাইল',
                'নাসিরনগর',
                'নবীনগর',
                'বাঞ্ছারামপুর',
                'কসবা',
                'আখাউড়া',
                'বিজয়নগর'
            ],
            'নোয়াখালী' => [
                'নোয়াখালী সদর',
                'বেগমগঞ্জ',
                'চাটখিল',
                'সোনাইমুড়ী',
                'সেনবাগ',
                'কোম্পানীগঞ্জ',
                'কবিরহাট',
                'সুবর্ণচর',
                'হাতিয়া'
            ],
            'লক্ষ্মীপুর' => [
                'লক্ষ্মীপুর সদর',
                'রায়পুর',
                'রামগঞ্জ',
                'রামগতি',
                'কমলনগর'
            ],
            'চাঁদপুর' => [
                'চাঁদপুর সদর',
                'হাজীগঞ্জ',
                'কচুয়া',
                'ফরিদগঞ্জ',
                'শাহরাস্তি',
                'মতলব উত্তর',
                'মতলব দক্ষিণ',
                'হাইমচর'
            ],
            'রাঙ্গামাটি' => [
                'রাঙ্গামাটি সদর',
                'কাপ্তাই',
                'বাঘাইছড়ি',
                'বরকল',
                'বিলাইছড়ি',
                'জুরাছড়ি',
                'নানিয়ারচর',
                'রাজস্থলী',
                'লংগদু',
                'কাউখালী'
            ],
            'খাগড়াছড়ি' => [
                'খাগড়াছড়ি সদর',
                'দীঘিনালা',
                'পানছড়ি',
                'মহালছড়ি',
                'মাটিরাঙ্গা',
                'মানিকছড়ি',
                'রামগড়',
                'লক্ষ্মীছড়ি',
                'গুইমারা'
            ],
            'বান্দরবান' => [
                'বান্দরবান সদর',
                'রুমা',
                'রোয়াংছড়ি',
                'থানচি',
                'লামা',
                'আলীকদম',
                'নাইক্ষ্যংছড়ি'
            ]
        ],
        'খুলনা' => [
            'খুলনা' => [
                'খুলনা সদর',
                'সোনাডাঙ্গা',
                'খালিশপুর',
                'দৌলতপুর',
                'রূপসা',
                'তেরখাদা',
                'দিঘলিয়া',
                'ফুলতলা',
                'ডুমুরিয়া',
                'বটিয়াঘাটা',
                'পাইকগাছা',
                'দাকোপ',
                'কয়রা'
            ],
            'যশোর' => [
                'যশোর সদর',
                'ঝিকরগাছা',
                'শার্শা',
                'মণিরামপুর',
                'কেশবপুর',
                'অভয়নগর',
                'বাঘারপাড়া',
                'চৌগাছা'
            ],
            'সাতক্ষীরা' => [
                'সাতক্ষীরা সদর',
                'কলারোয়া',
                'তালা',
                'দেবহাটা',
                'কালীগঞ্জ',
                'আশাশুনি',
                'শ্যামনগর'
            ],
            'ঝিনাইদহ' => [
                'ঝিনাইদহ সদর',
                'শৈলকুপা',
                'হরিণাকুণ্ডু',
                'কালীগঞ্জ',
                'কোটচাঁদপুর',
                'মহেশপুর'
            ],
            'মাগুরা' => [
                'মাগুরা সদর',
                'শ্রীপুর',
                'মহম্মদপুর',
                'শালিখা'
            ],
            'নড়াইল' => [
                'নড়াইল সদর',
                'লোহাগড়া',
                'কালিয়া'
            ],
            'বাগেরহাট' => [
                'বাগেরহাট সদর',
                'ফকিরহাট',
                'মোংলা',
                'রামপাল',
                'কচুয়া',
                'মোল্লাহাট',
                'মোরেলগঞ্জ',
                'শরণখোলা',
                'চিতলমারী'
            ],
            'কুষ্টিয়া' => [
                'কুষ্টিয়া সদর',
                'কুমারখালী',
                'খোকসা',
                'মিরপুর',
                'ভেড়ামারা',
                'দৌলতপুর'
            ],
            'চুয়াডাঙ্গা' => [
                'চুয়াডাঙ্গা সদর',
                'আলমডাঙ্গা',
                'দামুড়হুদা',
                'জীবননগর'
            ],
            'মেহেরপুর' => [
                'মেহেরপুর সদর',
                'গাংনী',
                'মুজিবনগর'
            ]
        ],
        'বরিশাল' => [
            'বরিশাল' => [
                'বরিশাল সদর',
                'বাবুগঞ্জ',
                'উজিরপুর',
                'বানারীপাড়া',
                'বাকেরগঞ্জ',
                'গৌরনদী',
                'আগৈলঝাড়া',
                'মেহেন্দিগঞ্জ',
                'হিজলা',
                'মুলাদী'
            ],
            'পটুয়াখালী' => [
                'পটুয়াখালী সদর',
                'কলাপাড়া',
                'বাউফল',
                'দশমিনা',
                'গলাচিপা',
                'মির্জাগঞ্জ',
                'দুমকি',
                'রাঙ্গাবালী'
            ],
            'ভোলা' => [
                'ভোলা সদর',
                'বোরহানউদ্দিন',
                'দৌলতখান',
                'তজুমদ্দিন',
                'লালমোহন',
                'চরফ্যাশন',
                'মনপুরা'
            ],
            'পিরোজপুর' => [
                'পিরোজপুর সদর',
                'ভান্ডারিয়া',
                'মঠবাড়িয়া',
                'কাউখালী',
                'নাজিরপুর',
                'নেছারাবাদ (স্বরূপকাঠি)',
                'ইন্দুরকানী'
            ],
            'বরগুনা' => [
                'বরগুনা সদর',
                'আমতলী',
                'তালতলী',
                'পাথরঘাটা',
                'বেতাগী',
                'বামনা'
            ],
            'ঝালকাঠি' => [
                'ঝালকাঠি সদর',
                'নলছিটি',
                'রাজাপুর',
                'কাঁঠালিয়া'
            ]
        ],
        'সিলেট' => [
            'সিলেট' => [
                'সিলেট সদর',
                'দক্ষিণ সুরমা',
                'বিশ্বনাথ',
                'গোলাপগঞ্জ',
                'বিয়ানীবাজার',
                'জকিগঞ্জ',
                'কানাইঘাট',
                'কোম্পানীগঞ্জ',
                'গোয়াইনঘাট',
                'জৈন্তাপুর',
                'ফেঞ্চুগঞ্জ',
                'বালাগঞ্জ',
                'ওসমানীনগর'
            ],
            'মৌলভীবাজার' => [
                'মৌলভীবাজার সদর',
                'শ্রীমঙ্গল',
                'কমলগঞ্জ',
                'কুলাউড়া',
                'বড়লেখা',
                'জুড়ী',
                'রাজনগর'
            ],
            'হবিগঞ্জ' => [
                'হবিগঞ্জ সদর',
                'মাধবপুর',
                'চুনারুঘাট',
                'বাহুবল',
                'নবীগঞ্জ',
                'লাখাই',
                'বানিয়াচং',
                'আজমিরীগঞ্জ',
                'শায়েস্তাগঞ্জ'
            ],
            'সুনামগঞ্জ' => [
                'সুনামগঞ্জ সদর',
                'শান্তিগঞ্জ (দক্ষিণ সুনামগঞ্জ)',
                'ছাতক',
                'জগন্নাথপুর',
                'দিরাই',
                'শাল্লা',
                'দোয়ারাবাজার',
                'ধর্মপাশা',
                'জামালগঞ্জ',
                'তাহিরপুর',
                'বিশ্বম্ভরপুর',
                'মধ্যনগর'
            ]
        ],
        'রংপুর' => [
            'রংপুর' => [
                'রংপুর সদর',
                'বদরগঞ্জ',
                'গংগাচড়া',
                'কাউনিয়া',
                'পীরগাছা',
                'মিঠাপুকুর',
                'পীরগঞ্জ',
                'তারাগঞ্জ'
            ],
            'দিনাজপুর' => [
                'দিনাজপুর সদর',
                'বিরল',
                'বোচাগঞ্জ',
                'কাহারোল',
                'বীরগঞ্জ',
                'খানসামা',
                'চিরিরবন্দর',
                'পার্বতীপুর',
                'ফুলবাড়ী',
                'নবাবগঞ্জ',
                'বিরামপুর',
                'হাকিমপুর',
                'ঘোড়াঘাট'
            ],
            'গাইবান্ধা' => [
                'গাইবান্ধা সদর',
                'গোবিন্দগঞ্জ',
                'সুন্দরগঞ্জ',
                'সাঘাটা',
                'পলাশবাড়ী',
                'সাদুল্লাপুর',
                'ফুলছড়ি'
            ],
            'কুড়িগ্রাম' => [
                'কুড়িগ্রাম সদর',
                'উলিপুর',
                'নাগেশ্বরী',
                'ভুরুঙ্গামারী',
                'চিলমারী',
                'রৌমারী',
                'চর রাজিবপুর',
                'রাজারহাট',
                'ফুলবাড়ী'
            ],
            'লালমনিরহাট' => [
                'লালমনিরহাট সদর',
                'হাতীবান্ধা',
                'পাটগ্রাম',
                'আদিতমারী',
                'কালীগঞ্জ'
            ],
            'নীলফামারী' => [
                'নীলফামারী সদর',
                'সৈয়দপুর',
                'ডোমার',
                'ডিমলা',
                'জলঢাকা',
                'কিশোরগঞ্জ'
            ],
            'পঞ্চগড়' => [
                'পঞ্চগড় সদর',
                'তেঁতুলিয়া',
                'বোদা',
                'দেবীগঞ্জ',
                'আটোয়ারী'
            ],
            'ঠাকুরগাঁও' => [
                'ঠাকুরগাঁও সদর',
                'পীরগঞ্জ',
                'রাণীশংকৈল',
                'বালিয়াডাঙ্গী',
                'হরিপুর'
            ]
        ],
        'ময়মনসিংহ' => [
            'ময়মনসিংহ' => [
                'ময়মনসিংহ সদর',
                'মুক্তাগাছা',
                'ফুলবাড়িয়া',
                'ত্রিশাল',
                'ভালুকা',
                'গফরগাঁও',
                'নান্দাইল',
                'ঈশ্বরগঞ্জ',
                'গৌরীপুর',
                'ফুলপুর',
                'তারাকান্দা',
                'হালুয়াঘাট',
                'ধোবাউড়া'
            ],
            'জামালপুর' => [
                'জামালপুর সদর',
                'সরিষাবাড়ী',
                'মেলান্দহ',
                'ইসলামপুর',
                'দেওয়ানগঞ্জ',
                'বকশীগঞ্জ',
                'মাদারগঞ্জ'
            ],
            'নেত্রকোণা' => [
                'নেত্রকোণা সদর',
                'কলমাকান্দা',
                'দুর্গাপুর',
                'পূর্বধলা',
                'বারহাট্টা',
                'কেন্দুয়া',
                'মদন',
                'মোহনগঞ্জ',
                'খালিয়াজুড়ি',
                'আটপাড়া'
            ],
            'শেরপুর' => [
                'শেরপুর সদর',
                'নালিতাবাড়ী',
                'নকলা',
                'শ্রীবরদী',
                'ঝিনাইগাতী'
            ]
        ]
    ];

    return $geo;
}

function get_divisions_list(): array {
    return array_keys(get_bangladesh_geo());
}

/**
 * Render "আমার এলাকার খবর" (My Area News) Cascading Filter Bar
 * Division -> District -> Upazila tree selector with red Search button
 *
 * @param array $selected ['division' => ..., 'district' => ..., 'upazila' => ...]
 * @param bool $isSidebar Whether rendering in narrow sidebar (stacks vertically) or wide main area
 * @param string $customTitle Section title heading
 * @return string HTML output
 */
function render_area_filter_bar(array $selected = [], bool $isSidebar = false, string $customTitle = 'আমার এলাকার খবর'): string {
    require_once __DIR__ . '/helpers.php';
    
    $geo = get_bangladesh_geo();
    $divisions = array_keys($geo);
    
    $currDiv = $selected['division'] ?? '';
    $currDist = $selected['district'] ?? '';
    $currUpz = $selected['upazila'] ?? '';

    static $filterBarCount = 0;
    $filterBarCount++;
    $uid = $filterBarCount;
    $formId = "area_form_{$uid}";
    $divId = "area_div_{$uid}";
    $distId = "area_dist_{$uid}";
    $upzId = "area_upz_{$uid}";

    ob_start();
    ?>
    <div class="area-filter-widget <?= $isSidebar ? 'area-filter-sidebar mb-4' : 'area-filter-main my-3' ?>">
        <div class="d-flex align-items-center mb-2">
            <h5 class="fw-bold text-dark mb-0 d-inline-flex align-items-center" style="font-size: <?= $isSidebar ? '1.05rem' : '1.25rem' ?>;">
                <?= e($customTitle) ?> <span class="text-danger ms-1 fw-bold fs-6">&gt;</span>
            </h5>
        </div>

        <div class="p-3 rounded-2 border" style="background-color: #f1f3f5;">
            <form action="/area" method="GET" class="row g-2 align-items-center" id="<?= $formId ?>">
                <!-- Division Select -->
                <div class="<?= $isSidebar ? 'col-12' : 'col-12 col-sm-6 col-md-3' ?>">
                    <select class="form-select bg-white" name="division" id="<?= $divId ?>" aria-label="বিভাগ নির্বাচন করুন">
                        <option value="">বিভাগ</option>
                        <?php foreach ($divisions as $d): ?>
                            <option value="<?= e($d) ?>" <?= ($currDiv === $d) ? 'selected' : '' ?>><?= e($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- District Select -->
                <div class="<?= $isSidebar ? 'col-12' : 'col-12 col-sm-6 col-md-3' ?>">
                    <select class="form-select bg-white" name="district" id="<?= $distId ?>" <?= empty($currDiv) ? 'disabled' : '' ?> aria-label="জেলা নির্বাচন করুন">
                        <option value="">জেলা</option>
                    </select>
                </div>

                <!-- Upazila Select -->
                <div class="<?= $isSidebar ? 'col-12' : 'col-12 col-sm-6 col-md-3' ?>">
                    <select class="form-select bg-white" name="upazila" id="<?= $upzId ?>" <?= empty($currDist) ? 'disabled' : '' ?> aria-label="উপজেলা নির্বাচন করুন">
                        <option value="">উপজেলা</option>
                    </select>
                </div>

                <!-- Search Button -->
                <div class="<?= $isSidebar ? 'col-12' : 'col-12 col-sm-6 col-md-3' ?>">
                    <button type="submit" class="btn btn-danger w-100 fw-bold d-flex align-items-center justify-content-center gap-2 text-white shadow-sm" style="background-color: #dc2626; border-color: #dc2626; height: 38px;">
                        <i class="bi bi-search"></i>
                        <span>খুঁজুন</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function() {
        const geoData = <?= json_encode($geo, JSON_UNESCAPED_UNICODE) ?>;
        const divSelect = document.getElementById('<?= $divId ?>');
        const distSelect = document.getElementById('<?= $distId ?>');
        const upzSelect = document.getElementById('<?= $upzId ?>');
        if (!divSelect || !distSelect || !upzSelect) return;

        function fillDistricts(selectedDiv, preselectedDist, preselectedUpz) {
            distSelect.innerHTML = '<option value="">জেলা</option>';
            upzSelect.innerHTML = '<option value="">উপজেলা</option>';
            upzSelect.disabled = true;

            if (!selectedDiv || !geoData[selectedDiv]) {
                distSelect.disabled = true;
                return;
            }

            distSelect.disabled = false;
            const districts = Object.keys(geoData[selectedDiv]);
            districts.forEach(function(d) {
                const opt = document.createElement('option');
                opt.value = d;
                opt.textContent = d;
                if (preselectedDist && preselectedDist === d) {
                    opt.selected = true;
                }
                distSelect.appendChild(opt);
            });

            if (preselectedDist) {
                fillUpazilas(selectedDiv, preselectedDist, preselectedUpz);
            }
        }

        function fillUpazilas(selectedDiv, selectedDist, preselectedUpz) {
            upzSelect.innerHTML = '<option value="">উপজেলা</option>';

            if (!selectedDist || !geoData[selectedDiv] || !geoData[selectedDiv][selectedDist]) {
                upzSelect.disabled = true;
                return;
            }

            upzSelect.disabled = false;
            const upazilas = geoData[selectedDiv][selectedDist];
            upazilas.forEach(function(u) {
                const opt = document.createElement('option');
                opt.value = u;
                opt.textContent = u;
                if (preselectedUpz && preselectedUpz === u) {
                    opt.selected = true;
                }
                upzSelect.appendChild(opt);
            });
        }

        divSelect.addEventListener('change', function() {
            fillDistricts(this.value);
        });

        distSelect.addEventListener('change', function() {
            fillUpazilas(divSelect.value, this.value);
        });

        // Initialize if preselected values exist
        const initialDiv = <?= json_encode($currDiv) ?>;
        const initialDist = <?= json_encode($currDist) ?>;
        const initialUpz = <?= json_encode($currUpz) ?>;
        if (initialDiv) {
            fillDistricts(initialDiv, initialDist, initialUpz);
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}



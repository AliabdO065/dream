<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Section;
use App\Models\User;
use App\Sections\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * The platform's own marketing site, shown at "/". It is an ordinary client (slug from
 * config platform.home_client), so it is edited in the dashboard exactly like any customer's site.
 * It uses all 15 section types, in Arabic and English.
 *
 * Sections whose content must be real facts (prices, reviews, team, support hours, contact details) are
 * added with draft text but HIDDEN — fill them in from the dashboard, then switch them on.
 *
 * Run with: php artisan db:seed --class=PlatformSiteSeeder  (ExampleSitesSeeder runs it after a full reset)
 */
class PlatformSiteSeeder extends Seeder
{
    /** English names for the example sites listed on the page (their slugs come from ExampleSitesSeeder). */
    private const EXAMPLES_EN = [
        'amaan-maintenance' => ['Home maintenance', 'Plumbing, electrics and AC with a callback form.'],
        'beit-alsham' => ['Restaurant', 'A menu with prices, event packages and table booking.'],
        'shifa-physio' => ['Physiotherapy centre', 'Treatments, session packages and a booking form.'],
        'adala-law' => ['Law firm', 'Practice areas, fees and a confidential enquiry form.'],
        'lamsa-salon' => ['Beauty salon', 'Services, price list and bridal packages.'],
        'powerfit-gym' => ['Gym', 'Classes, memberships and personal trainers.'],
        'adasa-studio' => ['Photo studio', 'Portfolio gallery and wedding packages.'],
        'batla-flowers' => ['Flower shop', 'Bouquets, prices and a weekly flower subscription.'],
        'nebras-academy' => ['Training academy', 'Courses, learning tracks and sign-ups.'],
        'reaya-charity' => ['Charity', 'Projects, monthly giving and volunteering.'],
        'rehlat-travel' => ['Travel agency', 'Trips, Umrah programmes and quote requests.'],
    ];

    public function run(Builder $b): void
    {
        $slug = config('platform.home_client');
        if (Client::where('slug', $slug)->exists()) {
            $this->command?->warn("Client [$slug] already exists — nothing changed.");

            return;
        }

        $c = Client::create([
            'slug' => $slug, 'name' => config('app.name'), 'business_type' => 'Website platform',
            'tagline' => 'منصة تبني لك موقعًا احترافيًا لنشاطك وتستقبل طلبات عملائك — بأي لغة وأي نشاط.',
            'default_locale' => 'ar', 'locales' => ['ar', 'en'],
            'theme' => ['brand' => '#4f46e5', 'accent' => '#f59e0b', 'font' => 'sans'], 'status' => 'active',
        ]);
        if ($admin = User::where('is_super_admin', true)->orderBy('id')->first()) {
            $c->users()->attach($admin->id, ['role' => 'owner']); // contact-form leads are emailed to the platform owner
        }

        // Product screenshots (database/seeders/assets) become this client's uploaded images.
        $img = function (string $name) use ($c): string {
            $dir = public_path("uploads/clients/{$c->id}");
            File::ensureDirectoryExists($dir);
            File::copy(database_path("seeders/assets/$name.png"), "$dir/$name.png");

            return "clients/{$c->id}/$name.png";
        };
        $hide = fn (Section $s) => $s->update(['is_enabled' => false]);

        // ---- 1 · Hero
        $s = $b->add($c, 'hero', 'Hero', [
            'eyebrow' => 'منصة مواقع لكل الأنشطة التجارية',
            'headline' => 'موقعك الاحترافي جاهز في دقائق',
            'subheadline' => "ابنِ صفحة نشاطك من أقسام جاهزة، واستقبل طلبات عملائك على بريدك،\nوأدِر كل شيء من لوحة تحكم واحدة — بأي لغة تريدها.",
            'cta_label' => 'ابدأ الآن', 'cta_url' => '#contact',
            'secondary_label' => 'شاهد نماذج حقيقية', 'secondary_url' => '#demos',
            'highlights' => [['text' => 'بدون أي كود'], ['text' => 'العربية والإنجليزية والألمانية وغيرها'], ['text' => 'طلبات العملاء تصل إلى بريدك']],
            'image' => $img('dashboard'),
        ], ['variant' => 'split', 'background' => 'brand']);
        $b->translate($s, 'en', [
            'eyebrow' => 'A website platform for every kind of business',
            'headline' => 'Your professional website, ready in minutes',
            'subheadline' => "Build your business page from ready-made sections, receive customer requests by email,\nand manage everything from one dashboard — in any language.",
            'cta_label' => 'Get started', 'secondary_label' => 'See real examples',
            'highlights' => [['text' => 'No code needed'], ['text' => 'Arabic, English, German and more'], ['text' => 'Customer requests land in your inbox']],
        ]);

        // ---- 2 · Numbers (facts about the product only)
        $s = $b->add($c, 'stats', 'Numbers', ['items' => [
            ['value' => (string) (count(config('platform.section_types')) + 1), 'label' => 'نوع قسم جاهز'], // + the contact form
            ['value' => (string) count(config('platform.languages')), 'label' => 'لغات مدعومة'],
            ['value' => (string) count(self::EXAMPLES_EN), 'label' => 'نموذج لأنشطة مختلفة'],
            ['value' => '0', 'label' => 'سطر كود مطلوب'],
        ]], ['background' => 'light']);
        $b->translate($s, 'en', ['items' => [
            ['label' => 'ready-made section types'], ['label' => 'supported languages'],
            ['label' => 'examples for different businesses'], ['label' => 'lines of code required'],
        ]]);

        // ---- 3 · Features
        $s = $b->add($c, 'list', 'Features', [
            'title' => 'كل ما يحتاجه نشاطك في مكان واحد',
            'subtitle' => 'أدوات بسيطة وقوية، بدون تعقيد.',
            'items' => [
                ['icon' => '🧩', 'title' => 'أقسام مرنة', 'text' => 'أضف وأخفِ ورتّب أي قسم في موقعك بضغطة زر.'],
                ['icon' => '🌍', 'title' => 'متعدد اللغات', 'text' => 'محتوى بأكثر من لغة، مع دعم كامل للكتابة من اليمين إلى اليسار.'],
                ['icon' => '📨', 'title' => 'طلبات العملاء', 'text' => 'كل طلب يصلك فورًا على بريدك، ويُحفظ في صندوق الطلبات.'],
                ['icon' => '🎨', 'title' => 'هويتك الخاصة', 'text' => 'لونك وشعارك وخطك، ليبدو الموقع كأنه مصمَّم لك وحدك.'],
                ['icon' => '👥', 'title' => 'فريق عمل', 'text' => 'أضف محررين يعدّلون المحتوى، وتبقى الصلاحيات الحساسة لك.'],
                ['icon' => '⚡', 'title' => 'سريع', 'text' => 'صفحات محفوظة مؤقتًا لتفتح بسرعة على أي جهاز.'],
            ],
        ], ['variant' => 'cards', 'background' => 'alt', 'show_in_nav' => true, 'nav_label' => 'المزايا'], 'features');
        $b->translate($s, 'en', [
            'title' => 'Everything your business needs in one place',
            'subtitle' => 'Simple, powerful tools — without the complexity.',
            'items' => [
                ['title' => 'Flexible sections', 'text' => 'Add, hide and reorder any section of your site with a click.'],
                ['title' => 'Multilingual', 'text' => 'Content in several languages, with full right-to-left support.'],
                ['title' => 'Customer requests', 'text' => 'Every request reaches your email instantly and is saved in your inbox.'],
                ['title' => 'Your own identity', 'text' => 'Your color, logo and font, so the site looks made just for you.'],
                ['title' => 'A team', 'text' => 'Add editors who update the content, while the sensitive settings stay with you.'],
                ['title' => 'Fast', 'text' => 'Cached pages that open quickly on any device.'],
            ],
        ], ['nav_label' => 'Features']);

        // ---- 4 · Text: the sections editor
        $s = $b->add($c, 'text', 'Sections editor', [
            'title' => 'رتّب موقعك بالسحب والإفلات',
            'body' => "أضف أقسامًا جاهزة — الواجهة، الخدمات، الأسعار، الأسئلة الشائعة — ثم اسحبها لتغيير ترتيبها، أو أخفِ ما لا تحتاجه بضغطة زر.\nكل تغيير يظهر على موقعك فورًا.",
            'image' => $img('sections'),
        ], ['variant' => 'image-left', 'background' => 'light']);
        $b->translate($s, 'en', [
            'title' => 'Arrange your site by dragging',
            'body' => "Add ready-made sections — hero, services, prices, FAQ — then drag them into the order you want, or hide what you do not need with one click.\nEvery change is live on your site right away.",
        ]);

        // ---- 5 · How it works
        $s = $b->add($c, 'list', 'How it works', [
            'title' => 'كيف يعمل؟',
            'items' => [
                ['title' => 'اختر نوع نشاطك', 'text' => 'نجهّز لك موقعًا مبدئيًا بأقسام تناسب نشاطك.'],
                ['title' => 'خصّص المحتوى', 'text' => 'عدّل النصوص والصور والألوان والأقسام من لوحة التحكم.'],
                ['title' => 'شارك رابطك', 'text' => 'يعمل موقعك على رابط باسمك، وتصلك طلبات العملاء على بريدك.'],
            ],
        ], ['variant' => 'numbered', 'background' => 'alt', 'show_in_nav' => true, 'nav_label' => 'كيف يعمل'], 'how');
        $b->translate($s, 'en', [
            'title' => 'How it works',
            'items' => [
                ['title' => 'Pick your kind of business', 'text' => 'We set up a starter site with sections that fit it.'],
                ['title' => 'Make it yours', 'text' => 'Edit texts, images, colors and sections from the dashboard.'],
                ['title' => 'Share your link', 'text' => 'Your site lives at a link with your name, and customer requests reach your email.'],
            ],
        ], ['nav_label' => 'How it works']);

        // ---- 6 · Examples: one real site per kind of business
        $examples = ExampleSitesSeeder::examples();
        $s = $b->add($c, 'list', 'Examples', [
            'title' => 'لأي نشاط تجاري',
            'subtitle' => 'منصة واحدة، ومواقع مختلفة تمامًا. كل نموذج يستخدم كل أنواع الأقسام — جرّبها:',
            'items' => array_map(fn ($e) => [
                'icon' => $e['icon'], 'title' => $e['name'], 'text' => $e['pitch'], 'link' => '/' . $e['slug'], 'link_label' => 'شاهد النموذج',
            ], $examples),
        ], ['variant' => 'cards', 'background' => 'light', 'show_in_nav' => true, 'nav_label' => 'نماذج'], 'demos');
        $b->translate($s, 'en', [
            'title' => 'For any kind of business',
            'subtitle' => 'One platform, completely different sites. Every example uses every kind of section — try them:',
            'items' => array_map(fn ($e) => [
                'title' => self::EXAMPLES_EN[$e['slug']][0] ?? $e['name'], 'text' => self::EXAMPLES_EN[$e['slug']][1] ?? '', 'link_label' => 'View example',
            ], $examples),
        ], ['nav_label' => 'Examples']);

        // ---- 7 · Gallery: the real dashboard
        $s = $b->add($c, 'gallery', 'Screenshots', [
            'title' => 'جولة في لوحة التحكم',
            'images' => [
                ['image' => $img('dashboard'), 'caption' => 'نظرة عامة على موقعك وطلباتك'],
                ['image' => $img('sections'), 'caption' => 'محرر الأقسام'],
                ['image' => $img('leads'), 'caption' => 'صندوق طلبات العملاء'],
            ],
        ], ['background' => 'alt']);
        $b->translate($s, 'en', [
            'title' => 'A tour of the dashboard',
            'images' => [['caption' => 'An overview of your site and requests'], ['caption' => 'The sections editor'], ['caption' => 'The customer requests inbox']],
        ]);

        // ---- 8 · Text: the leads inbox
        $s = $b->add($c, 'text', 'Leads inbox', [
            'title' => 'لا تفوّت أي طلب من عملائك',
            'body' => "كل رسالة من نموذج التواصل تصلك على بريدك وتُحفظ في صندوق الطلبات: تقرؤها، تعلّمها، وتردّ على العميل مباشرة.\nوترى عدد الطلبات الجديدة دائمًا على لوحتك.",
            'image' => $img('leads'),
        ], ['variant' => 'image-right', 'background' => 'light']);
        $b->translate($s, 'en', [
            'title' => 'Never miss a customer request',
            'body' => "Every message from the contact form lands in your email and is saved in your inbox: read it, flag it, and reply to the customer directly.\nThe number of new requests is always visible on your dashboard.",
        ]);

        // ---- 9 · Comparison
        $s = $b->add($c, 'comparison_table', 'Comparison', [
            'title' => 'لماذا منصتنا بدل شركة تصميم؟', 'subtitle' => 'مقارنة سريعة.',
            'our_label' => config('app.name'), 'competitor_label' => 'شركة تصميم تقليدية',
            'rows' => [
                ['feature' => 'موقع جاهز في نفس اليوم', 'ours' => true, 'competitor' => false],
                ['feature' => 'تعدّل المحتوى بنفسك بدون مبرمج', 'ours' => true, 'competitor' => false],
                ['feature' => 'نموذج طلبات يصل إلى بريدك', 'ours' => true, 'competitor' => true],
                ['feature' => 'أكثر من لغة بدون تكلفة إضافية', 'ours' => true, 'competitor' => false],
                ['feature' => 'تحديثات وتحسينات مستمرة', 'ours' => true, 'competitor' => false],
            ],
        ], ['background' => 'alt']);
        $b->translate($s, 'en', [
            'title' => 'Why us instead of a web agency?', 'subtitle' => 'A quick comparison.', 'our_label' => config('app.name'), 'competitor_label' => 'Traditional web agency',
            'rows' => [['feature' => 'A ready site the same day'], ['feature' => 'Edit the content yourself, no developer'], ['feature' => 'A request form that reaches your email'], ['feature' => 'Several languages at no extra cost'], ['feature' => 'Continuous updates and improvements']],
        ]);

        // ---- 10 · Plans (HIDDEN: put your real prices in, then switch it on)
        $s = $b->add($c, 'plans', 'Pricing', [
            'title' => 'باقات بسيطة', 'subtitle' => 'اختر الباقة المناسبة لنشاطك.',
            'items' => [
                ['name' => 'أساسي', 'price' => '—', 'period' => '/ شهر', 'description' => 'لنشاط صغير', 'features' => "موقع بلغة واحدة\nنموذج تواصل\nرابط باسم نشاطك", 'button_label' => 'ابدأ', 'button_url' => '#contact', 'highlighted' => false],
                ['name' => 'احترافي', 'price' => '—', 'period' => '/ شهر', 'description' => 'الأكثر طلبًا', 'features' => "كل ما في الأساسي\nحتى ٣ لغات\nمحررون لفريقك", 'button_label' => 'ابدأ', 'button_url' => '#contact', 'highlighted' => true],
                ['name' => 'مؤسسات', 'price' => '—', 'period' => '', 'description' => 'لعدة فروع', 'features' => "كل اللغات\nدعم مخصص\nإعداد كامل من فريقنا", 'button_label' => 'تواصل معنا', 'button_url' => '#contact', 'highlighted' => false],
            ],
        ], ['background' => 'light', 'show_in_nav' => true, 'nav_label' => 'الأسعار'], 'pricing');
        $b->translate($s, 'en', [
            'title' => 'Simple plans', 'subtitle' => 'Choose the plan that fits your business.',
            'items' => [
                ['name' => 'Basic', 'period' => '/ month', 'description' => 'For a small business', 'features' => "One-language site\nContact form\nA link with your business name", 'button_label' => 'Start'],
                ['name' => 'Professional', 'period' => '/ month', 'description' => 'Most popular', 'features' => "Everything in Basic\nUp to 3 languages\nEditors for your team", 'button_label' => 'Start'],
                ['name' => 'Enterprise', 'description' => 'For several branches', 'features' => "All languages\nDedicated support\nFull setup by our team", 'button_label' => 'Contact us'],
            ],
        ], ['nav_label' => 'Pricing']);
        $hide($s);

        // ---- 11 · Price list of extras (HIDDEN until real prices are in)
        $s = $b->add($c, 'priced_list', 'Extras', [
            'title' => 'خدمات إضافية', 'subtitle' => 'نساعدك إن أردت.',
            'items' => [
                ['group' => 'الإعداد', 'name' => 'إعداد الموقع بالكامل', 'description' => 'نكتب المحتوى ونرفع الصور', 'price' => '—'],
                ['name' => 'تصميم شعار', 'description' => '٣ مقترحات', 'price' => '—'],
                ['group' => 'المحتوى', 'name' => 'ترجمة لغة إضافية', 'description' => 'لكل الموقع', 'price' => '—'],
            ],
        ], ['background' => 'alt']);
        $b->translate($s, 'en', [
            'title' => 'Extra services', 'subtitle' => 'We can help if you like.',
            'items' => [
                ['group' => 'Setup', 'name' => 'Full site setup', 'description' => 'We write the content and upload the images'],
                ['name' => 'Logo design', 'description' => '3 proposals'],
                ['group' => 'Content', 'name' => 'Translation into another language', 'description' => 'For the whole site'],
            ],
        ]);
        $hide($s);

        // ---- 12 · Reviews (HIDDEN: replace with real customer quotes)
        $s = $b->add($c, 'reviews', 'Reviews', [
            'title' => 'ماذا يقول عملاؤنا',
            'items' => [
                ['author' => 'اسم العميل', 'rating' => '5', 'text' => 'اكتب هنا رأي عميل حقيقي.'],
                ['author' => 'اسم العميل', 'rating' => '5', 'text' => 'اكتب هنا رأي عميل حقيقي.'],
            ],
        ], ['background' => 'dark']);
        $b->translate($s, 'en', ['title' => 'What our customers say', 'items' => [['text' => 'A real customer quote goes here.'], ['text' => 'A real customer quote goes here.']]]);
        $hide($s);

        // ---- 13 · Team (HIDDEN: add the real team)
        $s = $b->add($c, 'team', 'Team', [
            'title' => 'فريقنا', 'subtitle' => 'الناس وراء المنصة.',
            'items' => [['name' => 'الاسم', 'role' => 'المنصب', 'bio' => 'نبذة قصيرة.']],
        ], ['background' => 'light']);
        $b->translate($s, 'en', ['title' => 'Our team', 'subtitle' => 'The people behind the platform.', 'items' => [['role' => 'Role', 'bio' => 'A short bio.']]]);
        $hide($s);

        // ---- 14 · FAQ
        $s = $b->add($c, 'faq', 'FAQ', [
            'title' => 'أسئلة شائعة',
            'items' => [
                ['question' => 'هل أحتاج إلى خبرة تقنية؟', 'answer' => 'لا. تعدّل كل شيء من لوحة تحكم بسيطة، بدون كتابة أي كود.'],
                ['question' => 'كيف سيصل العملاء إلى موقعي؟', 'answer' => 'لكل نشاط رابط باسمه على المنصة، تشاركه مع عملائك في أي مكان.'],
                ['question' => 'أين تصل طلبات العملاء؟', 'answer' => 'تُحفظ في صفحة «الطلبات» داخل لوحة التحكم، ويصلك تنبيه على بريدك الإلكتروني.'],
                ['question' => 'هل يمكن لفريقي العمل على الموقع؟', 'answer' => 'نعم. أضف محررين يعدّلون المحتوى، بينما يبقى حذف الأقسام وبيانات النشاط للمالك فقط.'],
                ['question' => 'هل يمكنني تغيير شكل الموقع؟', 'answer' => 'نعم: الألوان والشعار والخط وترتيب الأقسام، وإخفاء ما لا تحتاجه.'],
            ],
        ], ['background' => 'alt', 'show_in_nav' => true, 'nav_label' => 'الأسئلة الشائعة'], 'faq');
        $b->translate($s, 'en', [
            'title' => 'Frequently asked questions',
            'items' => [
                ['question' => 'Do I need technical skills?', 'answer' => 'No. You edit everything from a simple dashboard, with no code.'],
                ['question' => 'How will customers reach my site?', 'answer' => 'Every business gets a link with its own name on the platform, which you can share anywhere.'],
                ['question' => 'Where do customer requests go?', 'answer' => 'They are saved in the "Leads" page of your dashboard, and you get an email alert.'],
                ['question' => 'Can my team work on the site?', 'answer' => 'Yes. Add editors who update the content, while deleting sections and the business details stay with the owner.'],
                ['question' => 'Can I change how the site looks?', 'answer' => 'Yes: colors, logo, font and the order of sections, and you can hide anything you do not need.'],
            ],
        ], ['nav_label' => 'FAQ']);

        // ---- 15 · Support hours (HIDDEN: set your real hours)
        $s = $b->add($c, 'hours', 'Support hours', [
            'title' => 'مواعيد الدعم الفني',
            'rows' => [['label' => 'الأحد – الخميس', 'hours' => '٩ ص – ٥ م'], ['label' => 'الجمعة والسبت', 'hours' => 'عبر البريد فقط']],
            'note' => 'نرد على الرسائل خلال يوم عمل واحد.',
        ], ['background' => 'light']);
        $b->translate($s, 'en', [
            'title' => 'Support hours', 'rows' => [['label' => 'Sunday – Thursday', 'hours' => '9 am – 5 pm'], ['label' => 'Friday & Saturday', 'hours' => 'By email only']],
            'note' => 'We answer messages within one working day.',
        ]);
        $hide($s);

        // ---- 16 · Call to action
        $s = $b->add($c, 'cta', 'Call to action', [
            'title' => 'جاهز لإطلاق موقع نشاطك؟',
            'text' => 'تواصل معنا وسنجهّز لك حسابك.',
            'button_label' => 'تواصل معنا', 'button_url' => '#contact',
        ], ['background' => 'brand']);
        $b->translate($s, 'en', ['title' => 'Ready to launch your business site?', 'text' => "Get in touch and we'll set up your account.", 'button_label' => 'Contact us']);

        // ---- 17 · Contact form (leads are emailed to the platform owner)
        $s = $b->add($c, 'contact_form', 'Contact form', [
            'title' => 'تواصل معنا',
            'subtitle' => 'اترك بياناتك وسنعود إليك قريبًا.',
            'button_label' => 'أرسل الطلب',
            'success_message' => 'شكرًا لك! وصلنا طلبك وسنتواصل معك قريبًا.',
        ], ['background' => 'alt', 'show_in_nav' => true, 'nav_label' => 'تواصل معنا'], 'contact');
        $b->translate($s, 'en', [
            'title' => 'Contact us',
            'subtitle' => "Leave your details and we'll get back to you soon.",
            'button_label' => 'Send request',
            'success_message' => 'Thank you! We have received your request and will be in touch soon.',
        ], ['nav_label' => 'Contact']);

        // ---- 18 · Contact details (HIDDEN: fill the business profile's email/phone/address first)
        $s = $b->add($c, 'contact_info', 'Contact details', ['title' => 'بيانات التواصل', 'text' => 'يسعدنا تواصلك في أي وقت.'], ['background' => 'light']);
        $b->translate($s, 'en', ['title' => 'Contact details', 'text' => 'We are happy to hear from you any time.']);
        $hide($s);

        $this->command?->info("Platform marketing site created: open / (client [$slug]). Hidden until you fill them in: pricing, extras, reviews, team, support hours, contact details.");
    }
}

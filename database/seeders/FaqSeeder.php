<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FaqCategory;
use App\Models\FaqItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks for clean seed
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        FaqCategory::truncate();
        FaqItem::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $filePath = resource_path('views/faq/index.blade.php');
        if (!file_exists($filePath)) {
            $this->command->error("FAQ blade file not found at: {$filePath}");
            return;
        }

        $html = file_get_contents($filePath);

        // Predefined styles matching category classes
        $styles = [
            'integrasi'  => ['color' => '#6C63FF', 'color_rgb' => '108, 99, 255'],
            'produk'     => ['color' => '#8B5CF6', 'color_rgb' => '139, 92, 246'],
            'transaksi'  => ['color' => '#10B981', 'color_rgb' => '16, 185, 129'],
            'stok'       => ['color' => '#F59E0B', 'color_rgb' => '245, 158, 11'],
            'keuangan'   => ['color' => '#EF4444', 'color_rgb' => '239, 68, 68'],
            'chat'       => ['color' => '#06B6D4', 'color_rgb' => '6, 182, 212'],
            'pos'        => ['color' => '#EC4899', 'color_rgb' => '236, 72, 153'],
            'akses'      => ['color' => '#8492A6', 'color_rgb' => '132, 146, 166'],
            'laporan'    => ['color' => '#3B82F6', 'color_rgb' => '59, 130, 246'],
            'voucher'    => ['color' => '#F59E0B', 'color_rgb' => '245, 158, 11'],
            'pengaturan' => ['color' => '#10B981', 'color_rgb' => '16, 185, 129'],
        ];

        // Find all content cards
        // Split file content or match cards between comments
        $pattern = '/<!-- ====================\s*(.*?)\s*==================== -->\s*<div class="content-card\s+[^"]*"\s+data-category="(?P<slug>[^"]*)">(?P<body>.*?)(?=<!-- ====================|<!-- Extra Help|\Z)/s';
        
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            $processedSlugs = [];
            $sortOrder = 0;

            foreach ($matches as $match) {
                $slug = trim($match['slug']);
                
                // Skip duplicates (e.g. duplicate cat-akses)
                if (in_array($slug, $processedSlugs)) {
                    continue;
                }
                $processedSlugs[] = $slug;

                $body = $match['body'];

                // 1. Extract category icon
                $icon = 'fas fa-question-circle';
                if (preg_match('/<div class="content-header-icon"><i class="(?P<icon>[^"]+)"><\/i><\/div>/s', $body, $iconMatch)) {
                    $icon = trim($iconMatch['icon']);
                }

                // 2. Extract category title
                $title = 'Kategori';
                if (preg_match('/<h3>(?P<title>.*?)<\/h3>/s', $body, $titleMatch)) {
                    $title = trim($titleMatch['title']);
                }

                // 3. Extract category subtitle
                $subtitle = '';
                if (preg_match('/<div class="content-header-title">.*?<p>(?P<subtitle>.*?)<\/p>/s', $body, $subMatch)) {
                    $subtitle = trim($subMatch['subtitle']);
                }

                // 4. Extract read time
                $readTime = '';
                if (preg_match('/<div>Estimasi baca:\s*(?P<read_time>.*?)<\/div>/si', $body, $readMatch)) {
                    $readTime = trim($readMatch['read_time']);
                }

                // 5. Extract workflow title
                $workflowTitle = 'Alur Kerja';
                if (preg_match('/<div class="section-title-wrap">.*?<h4>(?P<workflow_title>.*?)<\/h4>/si', $body, $wfMatch)) {
                    $workflowTitle = trim($wfMatch['workflow_title']);
                }

                $color = $styles[$slug]['color'] ?? '#6C63FF';
                $colorRgb = $styles[$slug]['color_rgb'] ?? '108, 99, 255';

                // Create Category
                $category = FaqCategory::create([
                    'slug'           => $slug,
                    'name'           => $title,
                    'subtitle'       => $subtitle,
                    'icon'           => $icon,
                    'color'          => $color,
                    'color_rgb'      => $colorRgb,
                    'read_time'      => $readTime,
                    'workflow_title' => $workflowTitle,
                    'sort_order'     => $sortOrder++,
                ]);

                // 6. Extract Workflow steps directly from the body
                $stepPattern = '/<div class="timeline-item">.*?<div class="timeline-number">(?P<step_number>\d+)<\/div>.*?<div class="timeline-content">.*?<h5>(?P<title>.*?)<\/h5>.*?<p>(?P<desc>.*?)<\/p>/s';
                if (preg_match_all($stepPattern, $body, $steps, PREG_SET_ORDER)) {
                    foreach ($steps as $step) {
                        // Strip HTML tags from title and content to make them clean database text
                        $stepTitle = trim(strip_tags($step['title'], '<strong><span>'));
                        $stepDesc = trim(strip_tags($step['desc'], '<strong><span><strong style="color:var(--success);">'));
                        
                        FaqItem::create([
                            'faq_category_id' => $category->id,
                            'type'            => 'workflow',
                            'title'           => $stepTitle,
                            'content'         => $stepDesc,
                            'sort_order'      => (int) $step['step_number'],
                        ]);
                    }
                }

                // 7. Extract FAQs directly from the body
                $faqPattern = '/<div class="accordion-item">.*?<button class="accordion-trigger">(?P<question>.*?)\s*<i.*?>.*?<\/button>.*?<div class="accordion-content">(?P<answer>.*?)<\/div>/s';
                if (preg_match_all($faqPattern, $body, $faqs, PREG_SET_ORDER)) {
                    foreach ($faqs as $idx => $faq) {
                        $question = trim(strip_tags($faq['question']));
                        // Keep strong tags in answers if any
                        $answer = trim(strip_tags($faq['answer'], '<strong><b><i><br>'));

                        FaqItem::create([
                            'faq_category_id' => $category->id,
                            'type'            => 'faq',
                            'title'           => $question,
                            'content'         => $answer,
                            'sort_order'      => $idx + 1,
                        ]);
                    }
                }
            }
        }
    }
}

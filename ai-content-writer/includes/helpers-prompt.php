<?php
function aicw_build_prompt($topic, $type, $tone = 'friendly', $lang = 'en', $keywords = '', $existing = '') {

    // 🔹 Supported language mapping
    $lang_map = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'hi' => 'Hindi',
        'ur' => 'Urdu',
        'ar' => 'Arabic',
        'zh' => 'Chinese'
    ];

    // 🔹 Language instruction (stronger prompt)
    $lang_name = $lang_map[$lang] ?? 'English';
    $lang_instruction = ($lang !== 'en')
        ? "IMPORTANT: The entire response must be written in $lang_name language. Do not use English translations."
        : "";

    // 🔹 SEO keyword instruction
    $seo = $keywords ? "Include these SEO keywords naturally: $keywords." : "";

    // 🔹 Build prompt according to type
    switch ($type) {
        case 'blog_post':
            $prompt = "You are a professional writer. Write a $tone blog post about '$topic'. $seo 
                       Include an engaging introduction, main points, and conclusion. 
                       $lang_instruction";
            break;

        case 'product_description':
            $prompt = "Write a $tone, persuasive product description for '$topic'. $seo 
                       Highlight the main features and benefits clearly. 
                       $lang_instruction";
            break;

        case 'faq':
            $prompt = "Create 5 $tone FAQs (question + short answer) about '$topic'. $seo 
                       $lang_instruction";
            break;

        case 'social_media':
            $prompt = "Write a $tone, catchy social media post about '$topic'. $seo 
                       Add 3 relevant hashtags. Keep it under 150 words. 
                       $lang_instruction";
            break;

        case 'email':
            $prompt = "Draft a $tone professional email about '$topic'. $seo 
                       Include a clear subject and body text. 
                       $lang_instruction";
            break;

        case 'rewrite':
            $prompt = "Rewrite the following text in a $tone style. Improve clarity and flow. 
                       $seo $lang_instruction
                       \n\nText:\n$existing";
            break;

        case 'summary':
            $prompt = "Summarize the following text in a $tone tone. $seo 
                       $lang_instruction
                       \n\nText:\n$existing";
            break;

        default:
            $prompt = "Write $tone content about '$topic'. $seo $lang_instruction";
    }

    return trim(preg_replace('/\s+/', ' ', $prompt));
}
?>

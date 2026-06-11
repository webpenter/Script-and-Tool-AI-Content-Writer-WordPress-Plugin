<?php
/**
 * Template Name: Single Blog (Plugin Custom Template)
 * Description: Custom modern single layout for CPT Blog with a professional sidebar.
 */

get_header();
?>

<!-- Custom CSS for Modern Blog Layout -->
<style>
  :root {
    --abm-primary: #3b82f6;
    --abm-primary-hover: #2563eb;
    --abm-text-dark: #1f2937;
    --abm-text-muted: #6b7280;
    --abm-bg-card: #ffffff;
    --abm-bg-light: #f9fafb;
    --abm-border: #e5e7eb;
    --abm-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --abm-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --abm-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --abm-radius-sm: 8px;
    --abm-radius-md: 12px;
    --abm-radius-lg: 16px;
  }

  body {
    background-color: var(--abm-bg-light);
    color: var(--abm-text-dark);
  }

  .abm-single-wrapper {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  }

  .abm-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 40px;
  }

  @media (min-width: 992px) {
    .abm-grid {
      grid-template-columns: 2.2fr 1fr;
    }
  }

  /* Main Content Card */
  .abm-main-content {
    background: var(--abm-bg-card);
    border: 1px solid var(--abm-border);
    border-radius: var(--abm-radius-lg);
    padding: 30px;
    box-shadow: var(--abm-shadow-md);
  }

  .abm-back-btn {
    display: inline-flex;
    align-items: center;
    font-size: 14px;
    font-weight: 500;
    color: var(--abm-primary);
    text-decoration: none;
    margin-bottom: 20px;
    transition: color 0.2s ease;
  }

  .abm-back-btn:hover {
    color: var(--abm-primary-hover);
  }

  .abm-post-title {
    font-size: 28px;
    font-weight: 800;
    line-height: 1.25;
    margin: 0 0 15px 0;
    color: #111827;
  }

  @media (min-width: 768px) {
    .abm-post-title {
      font-size: 36px;
    }
  }

  /* Meta styling */
  .abm-post-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 15px;
    font-size: 14px;
    color: var(--abm-text-muted);
    border-bottom: 1px solid var(--abm-border);
    padding-bottom: 20px;
    margin-bottom: 25px;
  }

  .abm-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .abm-meta-author {
    font-weight: 600;
    color: var(--abm-text-dark);
  }

  /* Featured Image */
  .abm-post-thumbnail {
    margin-bottom: 30px;
    border-radius: var(--abm-radius-md);
    overflow: hidden;
    box-shadow: var(--abm-shadow-sm);
  }

  .abm-post-thumbnail img {
    width: 100%;
    height: auto;
    max-height: 500px;
    object-fit: cover;
    display: block;
  }

  /* Article Content */
  .abm-article-body {
    font-size: 18px;
    line-height: 1.8;
    color: #374151;
  }

  .abm-article-body p {
    margin-bottom: 24px;
  }

  .abm-article-body h2 {
    font-size: 24px;
    font-weight: 700;
    margin-top: 40px;
    margin-bottom: 16px;
    color: #111827;
  }

  .abm-article-body h3 {
    font-size: 20px;
    font-weight: 600;
    margin-top: 30px;
    margin-bottom: 12px;
    color: #111827;
  }

  .abm-article-body ul, .abm-article-body ol {
    margin-bottom: 24px;
    padding-left: 20px;
  }

  .abm-article-body li {
    margin-bottom: 8px;
  }

  /* Tags Styling */
  .abm-post-tags {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid var(--abm-border);
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .abm-tag {
    background: #eff6ff;
    color: var(--abm-primary);
    padding: 6px 12px;
    font-size: 13px;
    font-weight: 500;
    border-radius: 9999px;
    text-decoration: none;
    transition: background 0.2s ease, color 0.2s ease;
  }

  .abm-tag:hover {
    background: var(--abm-primary);
    color: #ffffff;
  }

  /* Sidebar styling */
  .abm-sidebar {
    display: flex;
    flex-direction: column;
    gap: 30px;
  }

  .abm-sidebar-widget {
    background: var(--abm-bg-card);
    border: 1px solid var(--abm-border);
    border-radius: var(--abm-radius-lg);
    padding: 24px;
    box-shadow: var(--abm-shadow-md);
  }

  .abm-widget-title {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--abm-primary);
    display: inline-block;
  }

  .abm-sidebar-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .abm-sidebar-item {
    display: flex;
    gap: 12px;
    align-items: center;
  }

  .abm-sidebar-thumb {
    width: 64px;
    height: 64px;
    flex-shrink: 0;
    border-radius: var(--abm-radius-sm);
    overflow: hidden;
    background: #e5e7eb;
  }

  .abm-sidebar-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .abm-sidebar-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .abm-sidebar-post-title {
    font-size: 14px;
    font-weight: 600;
    line-height: 1.4;
    margin: 0;
  }

  .abm-sidebar-post-title a {
    color: #1f2937;
    text-decoration: none;
    transition: color 0.2s ease;
  }

  .abm-sidebar-post-title a:hover {
    color: var(--abm-primary);
  }

  .abm-sidebar-date {
    font-size: 12px;
    color: var(--abm-text-muted);
  }

  .abm-affiliate-box {
    margin-top: 30px;
    padding: 20px;
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    border-radius: 4px;
    font-size: 15px;
  }
</style>

<div class="abm-single-wrapper">
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <div class="abm-grid">
      <!-- Main Content -->
      <main class="abm-main-content">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="abm-back-btn">
          <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 6px;">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
          </svg>
          Back to Home
        </a>

        <h1 class="abm-post-title"><?php the_title(); ?></h1>

        <div class="abm-post-meta">
          <span class="abm-meta-item">
            <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
            </svg>
            <span class="abm-meta-author"><?php the_author(); ?></span>
          </span>

          <span class="abm-meta-item">
            <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M6 2a1 1 0 00-1-1h3a1 1 0 000 2H5V5h10V3h-2a1 1 0 100-2h3a1 1 0 001 1v14a1 1 0 00-1 1H3a1 1 0 00-1-1V2zm3 6a1 1 0 011-1h4a1 1 0 110 2h-4a1 1 0 01-1-1zm0 4a1 1 0 011-1h4a1 1 0 110 2h-4a1 1 0 01-1-1z" clip-rule="evenodd" />
            </svg>
            <?php echo get_the_date('F j, Y'); ?>
          </span>

          <?php
          $categories = get_the_category();
          if (!empty($categories)) :
          ?>
            <span class="abm-meta-item">
              <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd" />
              </svg>
              <?php echo esc_html($categories[0]->name); ?>
            </span>
          <?php endif; ?>
        </div>

        <?php if (has_post_thumbnail()) : ?>
          <div class="abm-post-thumbnail">
            <?php the_post_thumbnail('full'); ?>
          </div>
        <?php endif; ?>

        <article class="abm-article-body">
          <?php the_content(); ?>
        </article>

        <?php
        $tags = get_the_tags();
        if (!empty($tags)) :
        ?>
          <div class="abm-post-tags">
            <?php foreach ($tags as $tag) : ?>
              <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>" class="abm-tag">#<?php echo esc_html($tag->name); ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </main>

      <!-- Sidebar -->
      <aside class="abm-sidebar">
        <div class="abm-sidebar-widget">
          <h3 class="abm-widget-title">Latest Articles</h3>
          <ul class="abm-sidebar-list">
            <?php
            $current_id = get_the_ID();
            $recent_query = new WP_Query(array(
              'post_type'      => 'blog',
              'posts_per_page' => 5,
              'post__not_in'   => array($current_id),
              'post_status'    => 'publish'
            ));

            if ($recent_query->have_posts()) :
              while ($recent_query->have_posts()) : $recent_query->the_post();
            ?>
                <li class="abm-sidebar-item">
                  <div class="abm-sidebar-thumb">
                    <?php if (has_post_thumbnail()) : ?>
                      <?php the_post_thumbnail('thumbnail'); ?>
                    <?php else : ?>
                      <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#e5e7eb; color:#9ca3af;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                          <circle cx="8.5" cy="8.5" r="1.5"/>
                          <polyline points="21 15 16 10 5 21"/>
                        </svg>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="abm-sidebar-info">
                    <h4 class="abm-sidebar-post-title">
                      <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h4>
                    <span class="abm-sidebar-date"><?php echo get_the_date('M j, Y'); ?></span>
                  </div>
                </li>
            <?php
              endwhile;
              wp_reset_postdata();
            else :
              echo '<li>No other blogs found.</li>';
            endif;
            ?>
          </ul>
        </div>
      </aside>
    </div>
  <?php endwhile; endif; ?>
</div>

<?php
get_footer();

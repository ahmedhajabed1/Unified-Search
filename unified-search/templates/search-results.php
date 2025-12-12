<?php
/**
 * Custom Search Results Template
 * This template overrides the theme's search template
 */

get_header();

$search_query = get_search_query();
$settings = get_option('unified_search_settings', array());

// Query products and posts separately with limits
$products = array();
$posts = array();

// Query products (limit 12)
if (class_exists('WooCommerce')) {
    $product_args = array(
        'post_type' => 'product',
        'posts_per_page' => 12,
        'post_status' => 'publish',
        's' => $search_query,
    );
    
    $product_query = new WP_Query($product_args);
    
    if ($product_query->have_posts()) {
        while ($product_query->have_posts()) {
            $product_query->the_post();
            $products[] = get_the_ID();
        }
    }
    wp_reset_postdata();
}

// Query posts (limit 12)
$post_args = array(
    'post_type' => 'post',
    'posts_per_page' => 12,
    'post_status' => 'publish',
    's' => $search_query,
);

$post_query = new WP_Query($post_args);

if ($post_query->have_posts()) {
    while ($post_query->have_posts()) {
        $post_query->the_post();
        $posts[] = get_the_ID();
    }
}
wp_reset_postdata();

$total_results = count($products) + count($posts);
?>

<div class="us-search-results-page">
    <div class="us-search-page-container">
        
        <!-- Search Header -->
        <div class="us-search-page-header">
            <h1 class="us-search-page-title">
                <?php _e('Search Results for:', 'unified-search'); ?> 
                <span><?php echo esc_html($search_query); ?></span>
            </h1>
            
            <?php if ($total_results > 0) : ?>
                <p class="us-search-page-count">
                    <?php printf(_n('%s result found', '%s results found', $total_results, 'unified-search'), number_format_i18n($total_results)); ?>
                </p>
            <?php endif; ?>
            
            <!-- Search Form -->
            <div class="us-search-page-form">
                <?php echo do_shortcode('[unified_search]'); ?>
            </div>
        </div>
        
        <?php if (!empty($products) || !empty($posts)) : ?>
            
            <!-- Products Section -->
            <?php if (!empty($products)) : ?>
                <div class="us-search-section">
                    <h2 class="us-search-section-title">
                        🛍️ <?php printf(_n('%d Product', '%d Products', count($products), 'unified-search'), count($products)); ?>
                    </h2>
                    
                    <div class="us-search-results-grid">
                        <?php foreach ($products as $product_id) : 
                            $product = wc_get_product($product_id);
                            if (!$product) continue;
                        ?>
                            <div class="us-search-result-card">
                                <a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="us-card-link">
                                    
                                    <!-- Product Image -->
                                    <div class="us-card-image">
                                        <?php 
                                        if (has_post_thumbnail($product_id)) {
                                            echo get_the_post_thumbnail($product_id, 'medium');
                                        } else {
                                            echo '<img src="' . US_PLUGIN_URL . 'assets/images/placeholder.png" alt="No image" />';
                                        }
                                        ?>
                                    </div>
                                    
                                    <!-- Product Content -->
                                    <div class="us-card-content">
                                        <span class="us-card-badge product"><?php _e('Product', 'unified-search'); ?></span>
                                        
                                        <h3 class="us-card-title"><?php echo esc_html($product->get_name()); ?></h3>
                                        
                                        <?php if (!empty($settings['show_excerpt']) && $product->get_short_description()) : ?>
                                            <div class="us-card-excerpt">
                                                <?php echo wp_trim_words($product->get_short_description(), 20); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($settings['show_price'])) : ?>
                                            <div class="us-card-price-section">
                                                <div class="us-card-price">
                                                    <?php echo $product->get_price_html(); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                
                                <?php if (!empty($settings['show_add_to_cart']) && $product->is_purchasable() && $product->is_in_stock()) : ?>
                                    <div class="us-card-actions">
                                        <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" 
                                           class="us-add-to-cart-btn"
                                           data-product_id="<?php echo esc_attr($product_id); ?>">
                                            <?php _e('🛒 Add to Cart', 'unified-search'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Posts Section -->
            <?php if (!empty($posts)) : ?>
                <div class="us-search-section">
                    <h2 class="us-search-section-title">
                        📝 <?php printf(_n('%d Blog Post', '%d Blog Posts', count($posts), 'unified-search'), count($posts)); ?>
                    </h2>
                    
                    <div class="us-search-results-grid">
                        <?php foreach ($posts as $post_id) : 
                            $post = get_post($post_id);
                            if (!$post) continue;
                        ?>
                            <div class="us-search-result-card">
                                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="us-card-link">
                                    
                                    <!-- Post Thumbnail -->
                                    <div class="us-card-image">
                                        <?php 
                                        if (has_post_thumbnail($post_id)) {
                                            echo get_the_post_thumbnail($post_id, 'medium');
                                        } else {
                                            echo '<img src="' . US_PLUGIN_URL . 'assets/images/placeholder.png" alt="No image" />';
                                        }
                                        ?>
                                    </div>
                                    
                                    <!-- Post Content -->
                                    <div class="us-card-content">
                                        <span class="us-card-badge post"><?php _e('Post', 'unified-search'); ?></span>
                                        
                                        <h3 class="us-card-title"><?php echo esc_html(get_the_title($post_id)); ?></h3>
                                        
                                        <?php if (!empty($settings['show_excerpt'])) : ?>
                                            <div class="us-card-excerpt">
                                                <?php echo wp_trim_words(get_the_excerpt($post_id), 20); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="us-card-meta">
                                            <span class="us-meta-date"><?php echo get_the_date('', $post_id); ?></span>
                                            <span class="us-meta-author"><?php echo get_the_author_meta('display_name', $post->post_author); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else : ?>
            
            <!-- No Results -->
            <div class="us-no-results-page">
                <div class="us-no-results-icon">🔍</div>
                <h2 class="us-no-results-title"><?php _e('No Results Found', 'unified-search'); ?></h2>
                <p class="us-no-results-text">
                    <?php printf(__('Sorry, no results found for "%s". Please try a different search term.', 'unified-search'), esc_html($search_query)); ?>
                </p>
                
                <div class="us-search-suggestions">
                    <h3><?php _e('Search Tips:', 'unified-search'); ?></h3>
                    <ul>
                        <li><?php _e('Check your spelling', 'unified-search'); ?></li>
                        <li><?php _e('Try more general keywords', 'unified-search'); ?></li>
                        <li><?php _e('Try different keywords', 'unified-search'); ?></li>
                        <li><?php _e('Try fewer keywords', 'unified-search'); ?></li>
                    </ul>
                </div>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<?php get_footer(); ?>

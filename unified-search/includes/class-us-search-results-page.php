<?php
/**
 * Search Results Page Handler
 * Overrides theme search templates to display custom unified search results
 */

if (!defined('ABSPATH')) {
    exit;
}

class US_Search_Results_Page {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Modify search query to include products
        add_action('pre_get_posts', array($this, 'modify_search_query'));
        
        // Override search template - HIGH PRIORITY to override Elementor
        add_filter('template_include', array($this, 'load_custom_search_template'), 99);
        
        // Enqueue search page styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_search_page_styles'));
    }
    
    /**
     * Modify search query to include products
     */
    public function modify_search_query($query) {
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            $settings = get_option('unified_search_settings', array());
            $post_types = array();
            
            if (!empty($settings['search_posts'])) {
                $post_types[] = 'post';
            }
            
            if (!empty($settings['search_products']) && class_exists('WooCommerce')) {
                $post_types[] = 'product';
            }
            
            // If no post types selected, search both by default
            if (empty($post_types)) {
                $post_types = array('post');
                if (class_exists('WooCommerce')) {
                    $post_types[] = 'product';
                }
            }
            
            $query->set('post_type', $post_types);
            $query->set('posts_per_page', 20);
        }
    }
    
    /**
     * Load custom search template
     */
    public function load_custom_search_template($template) {
        if (is_search()) {
            $custom_template = US_PLUGIN_DIR . 'templates/search-results.php';
            
            // Create template file if it doesn't exist
            if (!file_exists($custom_template)) {
                $this->create_search_template();
            }
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }
    
    /**
     * Enqueue search page styles
     */
    public function enqueue_search_page_styles() {
        if (is_search()) {
            wp_enqueue_style('us-search-page-css', US_PLUGIN_URL . 'assets/css/search-page.css', array(), US_VERSION);
        }
    }
    
    /**
     * Create search template file
     */
    private function create_search_template() {
        $templates_dir = US_PLUGIN_DIR . 'templates';
        
        if (!file_exists($templates_dir)) {
            wp_mkdir_p($templates_dir);
        }
        
        $template_content = $this->get_search_template_content();
        file_put_contents($templates_dir . '/search-results.php', $template_content);
    }
    
    /**
     * Get search template content
     */
    private function get_search_template_content() {
        return '<?php
/**
 * Custom Search Results Template
 * This template overrides the theme\'s search template
 */

get_header();

$search_query = get_search_query();
$settings = get_option(\'unified_search_settings\', array());
?>

<div class="us-search-results-page">
    <div class="us-search-page-container">
        
        <!-- Search Header -->
        <div class="us-search-page-header">
            <h1 class="us-search-page-title">
                <?php _e(\'Search Results for:\', \'unified-search\'); ?> 
                <span><?php echo esc_html($search_query); ?></span>
            </h1>
            
            <?php if (have_posts()) : 
                global $wp_query;
                $total_results = $wp_query->found_posts;
            ?>
                <p class="us-search-page-count">
                    <?php printf(_n(\'%s result found\', \'%s results found\', $total_results, \'unified-search\'), number_format_i18n($total_results)); ?>
                </p>
            <?php endif; ?>
            
            <!-- Search Form -->
            <div class="us-search-page-form">
                <?php echo do_shortcode(\'[unified_search]\'); ?>
            </div>
        </div>
        
        <?php if (have_posts()) : ?>
            
            <?php
            // Group results by post type
            $products = array();
            $posts = array();
            
            while (have_posts()) : the_post();
                if (get_post_type() === \'product\') {
                    $products[] = get_the_ID();
                } else {
                    $posts[] = get_the_ID();
                }
            endwhile;
            
            // Reset post data
            wp_reset_postdata();
            ?>
            
            <!-- Products Section -->
            <?php if (!empty($products)) : ?>
                <div class="us-search-section">
                    <h2 class="us-search-section-title">
                        🛍️ <?php printf(_n(\'%d Product\', \'%d Products\', count($products), \'unified-search\'), count($products)); ?>
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
                                            echo get_the_post_thumbnail($product_id, \'medium\');
                                        } else {
                                            echo \'<img src="\' . US_PLUGIN_URL . \'assets/images/placeholder.png" alt="No image" />\';
                                        }
                                        ?>
                                    </div>
                                    
                                    <!-- Product Content -->
                                    <div class="us-card-content">
                                        <span class="us-card-badge product"><?php _e(\'Product\', \'unified-search\'); ?></span>
                                        
                                        <h3 class="us-card-title"><?php echo esc_html($product->get_name()); ?></h3>
                                        
                                        <?php if (!empty($settings[\'show_excerpt\']) && $product->get_short_description()) : ?>
                                            <div class="us-card-excerpt">
                                                <?php echo wp_trim_words($product->get_short_description(), 20); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($settings[\'show_price\'])) : ?>
                                            <div class="us-card-price-section">
                                                <div class="us-card-price">
                                                    <?php echo $product->get_price_html(); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                
                                <?php if (!empty($settings[\'show_add_to_cart\']) && $product->is_purchasable() && $product->is_in_stock()) : ?>
                                    <div class="us-card-actions">
                                        <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" 
                                           class="us-add-to-cart-btn"
                                           data-product_id="<?php echo esc_attr($product_id); ?>">
                                            <?php _e(\'🛒 Add to Cart\', \'unified-search\'); ?>
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
                        📝 <?php printf(_n(\'%d Blog Post\', \'%d Blog Posts\', count($posts), \'unified-search\'), count($posts)); ?>
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
                                            echo get_the_post_thumbnail($post_id, \'medium\');
                                        } else {
                                            echo \'<img src="\' . US_PLUGIN_URL . \'assets/images/placeholder.png" alt="No image" />\';
                                        }
                                        ?>
                                    </div>
                                    
                                    <!-- Post Content -->
                                    <div class="us-card-content">
                                        <span class="us-card-badge post"><?php _e(\'Post\', \'unified-search\'); ?></span>
                                        
                                        <h3 class="us-card-title"><?php echo esc_html(get_the_title($post_id)); ?></h3>
                                        
                                        <?php if (!empty($settings[\'show_excerpt\'])) : ?>
                                            <div class="us-card-excerpt">
                                                <?php echo wp_trim_words(get_the_excerpt($post_id), 20); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="us-card-meta">
                                            <span class="us-meta-date"><?php echo get_the_date(\'\', $post_id); ?></span>
                                            <span class="us-meta-author"><?php echo get_the_author_meta(\'display_name\', $post->post_author); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Pagination -->
            <?php if ($wp_query->max_num_pages > 1) : ?>
                <div class="us-search-pagination">
                    <?php
                    echo paginate_links(array(
                        \'total\' => $wp_query->max_num_pages,
                        \'current\' => max(1, get_query_var(\'paged\')),
                        \'format\' => \'?paged=%#%\',
                        \'prev_text\' => __(\'« Previous\', \'unified-search\'),
                        \'next_text\' => __(\'Next »\', \'unified-search\'),
                    ));
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else : ?>
            
            <!-- No Results -->
            <div class="us-no-results-page">
                <div class="us-no-results-icon">🔍</div>
                <h2 class="us-no-results-title"><?php _e(\'No Results Found\', \'unified-search\'); ?></h2>
                <p class="us-no-results-text">
                    <?php printf(__(\'Sorry, no results found for "%s". Please try a different search term.\', \'unified-search\'), esc_html($search_query)); ?>
                </p>
                
                <div class="us-search-suggestions">
                    <h3><?php _e(\'Search Tips:\', \'unified-search\'); ?></h3>
                    <ul>
                        <li><?php _e(\'Check your spelling\', \'unified-search\'); ?></li>
                        <li><?php _e(\'Try more general keywords\', \'unified-search\'); ?></li>
                        <li><?php _e(\'Try different keywords\', \'unified-search\'); ?></li>
                        <li><?php _e(\'Try fewer keywords\', \'unified-search\'); ?></li>
                    </ul>
                </div>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<?php get_footer(); ?>';
    }
}


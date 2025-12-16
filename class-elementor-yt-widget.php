<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Elementor_YT_Feed_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'yt_feed_widget';
    }

    public function get_title() {
        return esc_html__( 'YT Channel Feed', 'el-yt-feed' );
    }

    public function get_icon() {
        return 'eicon-youtube';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    public function get_script_depends() {
        return [ 'swiper' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__( 'Content', 'el-yt-feed' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'video_count',
            [
                'label' => esc_html__( 'Total Videos', 'el-yt-feed' ),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 2,
                'max' => 20,
                'default' => 6,
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $limit = $settings['video_count'];

        $args = array(
            'post_type' => 'yt_video',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish'
        );

        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            echo '<p>No videos found.</p>';
            return;
        }

        $posts = $query->posts;
        
        // 1. Extract Latest Video (Hero)
        $hero_video = array_shift( $posts );
        $hero_cat_terms = get_the_terms( $hero_video->ID, 'video_category' );
        $hero_cat = ( $hero_cat_terms && ! is_wp_error( $hero_cat_terms ) ) ? $hero_cat_terms[0]->name : '';
        $hero_date = get_the_date( '', $hero_video->ID );
        ?>

        <div class="yt-feed-wrapper">
            
            <div class="yt-hero-section">
                <div class="yt-hero-card">
                    <a href="<?php echo get_permalink($hero_video->ID); ?>" class="yt-hero-link">
                        <div class="yt-hero-thumb">
                            <?php echo get_the_post_thumbnail( $hero_video->ID, 'large' ); ?>
                            <div class="yt-play-icon"></div>
                        </div>
                        <div class="yt-hero-info">
                            <?php if($hero_cat): ?><span class="yt-cat-badge"><?php echo esc_html($hero_cat); ?></span><?php endif; ?>
                            <h2 class="yt-hero-title"><?php echo esc_html($hero_video->post_title); ?></h2>
                            <span class="yt-date"><?php echo esc_html($hero_date); ?></span>
                        </div>
                    </a>
                </div>
            </div>

            <?php if ( ! empty( $posts ) ) : ?>
                <div class="yt-carousel-section">
                    <h3 class="yt-carousel-heading">More Videos</h3>
                    
                    <div class="swiper-container yt-swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ( $posts as $post ) : 
                                $cat_terms = get_the_terms( $post->ID, 'video_category' );
                                $cat = ( $cat_terms && ! is_wp_error( $cat_terms ) ) ? $cat_terms[0]->name : '';
                                ?>
                                <div class="swiper-slide">
                                    <div class="yt-slide-card">
                                        <a href="<?php echo get_permalink($post->ID); ?>">
                                            <div class="yt-slide-thumb">
                                                <?php echo get_the_post_thumbnail( $post->ID, 'medium' ); ?>
                                            </div>
                                            <div class="yt-slide-body">
                                                <?php if($cat): ?><span class="yt-cat-text"><?php echo esc_html($cat); ?></span><?php endif; ?>
                                                <h4 class="yt-slide-title"><?php echo esc_html($post->post_title); ?></h4>
                                                <span class="yt-date-small"><?php echo get_the_date( '', $post->ID ); ?></span>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="swiper-pagination"></div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
                
                <script>
                jQuery(document).ready(function($) {
                    new Swiper('.yt-swiper', {
                        slidesPerView: 1,
                        spaceBetween: 20,
                        loop: false,
                        navigation: {
                            nextEl: '.swiper-button-next',
                            prevEl: '.swiper-button-prev',
                        },
                        pagination: {
                            el: '.swiper-pagination',
                            clickable: true,
                        },
                        breakpoints: {
                            640: { slidesPerView: 2 },
                            1024: { slidesPerView: 3 }
                        }
                    });
                });
                </script>
            <?php endif; ?>
            
        </div>
        <?php
    }
}
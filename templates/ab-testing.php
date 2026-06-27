<?php
/**
 * A/B Testing Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$cookienod_ab_testing = new CookieNod_AB_Testing();
$cookienod_tests = $cookienod_ab_testing->get_all_tests();
$cookienod_active_test = null;

foreach ($cookienod_tests as $cookienod_test) {
    if ($cookienod_test['status'] === 'active') {
        $cookienod_active_test = $cookienod_test;
        break;
    }
}
?>

<div class="wrap cookienod-ab-testing">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="cookienod-card">
        <div class="nav-tab-wrapper">
            <a href="#active-tests" class="nav-tab nav-tab-active"><?php esc_html_e('Active Test', 'cookienod-extended'); ?></a>
            <a href="#all-tests" class="nav-tab"><?php esc_html_e('All Tests', 'cookienod-extended'); ?></a>
            <a href="#create-test" class="nav-tab"><?php esc_html_e('Create Test', 'cookienod-extended'); ?></a>
        </div>

        <!-- Active Test -->
        <div id="active-tests" class="cookienod-tab-content" data-test-id="<?php echo $cookienod_active_test ? esc_attr($cookienod_active_test['id']) : ''; ?>">
            <?php if ($cookienod_active_test) : ?>
                <?php $cookienod_stats = $cookienod_ab_testing->get_test_stats($cookienod_active_test['id']); ?>
                <h2><?php echo esc_html($cookienod_active_test['name']); ?></h2>

                <div class="cookienod-ab-stats" data-test-id="<?php echo esc_attr($cookienod_active_test['id']); ?>">
                    <?php foreach (json_decode($cookienod_active_test['variants'], true) as $cookienod_variant) : ?>
                        <?php $cookienod_variant_stats = $cookienod_stats[$cookienod_variant['id']] ?? array(
                            'impressions' => 0,
                            'accept_all' => 0,
                            'reject_all' => 0,
                            'accept_rate' => 0,
                        ); ?>

                        <div class="cookienod-ab-variant">
                            <h3><?php echo esc_html($cookienod_variant['name']); ?></h3>
                            <div class="cookienod-ab-metrics">
                                <div class="metric">
                                    <span class="metric-value"><?php echo esc_html(number_format($cookienod_variant_stats['impressions'])); ?></span>
                                    <span class="metric-label"><?php esc_html_e('Impressions', 'cookienod-extended'); ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-value"><?php echo esc_html(number_format($cookienod_variant_stats['accept_all'])); ?></span>
                                    <span class="metric-label"><?php esc_html_e('Accepts', 'cookienod-extended'); ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-value"><?php echo esc_html(number_format($cookienod_variant_stats['accept_rate'])); ?>%</span>
                                    <span class="metric-label"><?php esc_html_e('Accept Rate', 'cookienod-extended'); ?></span>
                                </div>
                            </div>

                            <div class="cookienod-ab-actions">
                                <button class="button button-primary set-winner" data-variant="<?php echo esc_attr($cookienod_variant['id']); ?>">
                                    <?php esc_html_e('Set as Winner', 'cookienod-extended'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p>
                    <strong><?php esc_html_e('Start Date:', 'cookienod-extended'); ?></strong>
                    <?php echo esc_html($cookienod_active_test['start_date']); ?>
                </p>

                <button class="button" id="stop-test"><?php esc_html_e('Stop Test', 'cookienod-extended'); ?></button>

            <?php else : ?>
                <p><?php esc_html_e('No active A/B test. Create a new test to start optimizing your consent banner.', 'cookienod-extended'); ?></p>
                <a href="#create-test" class="button button-primary"><?php esc_html_e('Create Test', 'cookienod-extended'); ?></a>
            <?php endif; ?>
        </div>

        <!-- All Tests -->
        <div id="all-tests" class="cookienod-tab-content" style="display:none;">
            <?php if ($cookienod_tests) : ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'cookienod-extended'); ?></th>
                            <th><?php esc_html_e('Status', 'cookienod-extended'); ?></th>
                            <th><?php esc_html_e('Variants', 'cookienod-extended'); ?></th>
                            <th><?php esc_html_e('Start Date', 'cookienod-extended'); ?></th>
                            <th><?php esc_html_e('Winner', 'cookienod-extended'); ?></th>
                            <th><?php esc_html_e('Actions', 'cookienod-extended'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cookienod_tests as $cookienod_test) : ?>
                            <?php $cookienod_variants = json_decode($cookienod_test['variants'], true); ?>
                            <tr>
                                <td><?php echo esc_html($cookienod_test['name']); ?></td>
                                <td><span class="status-badge status-<?php echo esc_attr($cookienod_test['status']); ?>">
                                    <?php echo esc_html(ucfirst($cookienod_test['status'])); ?></span></td>
                                <td><?php echo count($cookienod_variants); ?></td>
                                <td><?php echo $cookienod_test['start_date'] ? esc_html($cookienod_test['start_date']) : '-'; ?></td>
                                <td>
                                    <?php if ($cookienod_test['winner']) : ?>
                                        <?php foreach ($cookienod_variants as $cookienod_v) {
                                            if ($cookienod_v['id'] == $cookienod_test['winner']) {
                                                echo esc_html($cookienod_v['name']);
                                                break;
                                            }
                                        } ?>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($cookienod_test['status'] === 'draft') : ?>
                                        <button class="button button-primary start-test-btn" data-test="<?php echo esc_attr($cookienod_test['id']); ?>">
                                            <?php esc_html_e('Start Test', 'cookienod-extended'); ?>
                                        </button>
                                    <?php elseif ($cookienod_test['status'] === 'active') : ?>
                                        <button class="button stop-test-btn" data-test="<?php echo esc_attr($cookienod_test['id']); ?>">
                                            <?php esc_html_e('Stop', 'cookienod-extended'); ?>
                                        </button>
                                    <?php else : ?>
                                        <span class="button button-secondary" disabled><?php esc_html_e('Completed', 'cookienod-extended'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No tests created yet.', 'cookienod-extended'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Create Test -->
        <div id="create-test" class="cookienod-tab-content" style="display:none;">
            <h2><?php esc_html_e('Create New A/B Test', 'cookienod-extended'); ?></h2>

            <form id="create-test-form">
                <table class="form-table">
                    <tr>
                        <th><label for="test-name"><?php esc_html_e('Test Name', 'cookienod-extended'); ?></label></th>
                        <td>
                            <input type="text" id="test-name" class="regular-text" required />
                            <p class="description"><?php esc_html_e('Give your test a descriptive name', 'cookienod-extended'); ?></p>
                        </td>
                    </tr>
                </table>

                <h3><?php esc_html_e('Variants', 'cookienod-extended'); ?></h3>

                <div id="test-variants">
                    <div class="test-variant" data-id="1">
                        <h4><?php esc_html_e('Variant A (Control)', 'cookienod-extended'); ?></h4>
                        <label><?php esc_html_e('Name', 'cookienod-extended'); ?></label>
                        <input type="text" class="variant-name" value="<?php echo esc_html_e('Original', 'cookienod-extended'); ?>" />

                        <label><?php esc_html_e('Banner Position', 'cookienod-extended'); ?></label>
                        <select class="variant-position">
                            <option value="bottom"><?php esc_html_e('Bottom', 'cookienod-extended'); ?></option>
                            <option value="top"><?php esc_html_e('Top', 'cookienod-extended'); ?></option>
                            <option value="center"><?php esc_html_e('Center', 'cookienod-extended'); ?></option>
                        </select>

                        <label><?php esc_html_e('Accept Button Color', 'cookienod-extended'); ?></label>
                        <input type="color" class="variant-primary-color" value="#10b981" />
                    </div>

                    <div class="test-variant" data-id="2">
                        <h4><?php esc_html_e('Variant B', 'cookienod-extended'); ?></h4>

                        <label><?php esc_html_e('Name', 'cookienod-extended'); ?></label>
                        <input type="text" class="variant-name" value="<?php echo esc_html_e('Test Variant', 'cookienod-extended'); ?>" />

                        <label><?php esc_html_e('Banner Position', 'cookienod-extended'); ?></label>
                        <select class="variant-position">
                            <option value="bottom"><?php esc_html_e('Bottom', 'cookienod-extended'); ?></option>
                            <option value="top"><?php esc_html_e('Top', 'cookienod-extended'); ?></option>
                            <option value="center" selected><?php esc_html_e('Center', 'cookienod-extended'); ?></option>
                        </select>

                        <label><?php esc_html_e('Accept Button Color', 'cookienod-extended'); ?></label>
                        <input type="color" class="variant-primary-color" value="#3b82f6" />
                    </div>
                </div>

                <p>
                    <button type="button" class="button" id="add-variant"><?php esc_html_e('Add Variant', 'cookienod-extended'); ?></button>
                </p>

                <h3><?php esc_html_e('Traffic Split', 'cookienod-extended'); ?></h3>

                <div id="traffic-split">
                    <input type="range" min="0" max="100" value="50" class="split-slider" />
                    <span class="split-display">50% / 50%</span>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Create Test', 'cookienod-extended'); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>

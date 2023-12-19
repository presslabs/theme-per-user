<?php
/**
 * Plugin Name: Theme per user
 * Description: Load one Theme for a specific user. You must set the theme from `User Profile` then logout and login again in order to take effect.
 * Version:     1.0.2
 * Author:      Presslabs
 * Author URI:  https://www.presslabs.com/
 * License:     GPLv3
 */
define('THEME_PER_USER_OPTION', 'theme_per_user');

//------------------------------------------------------------------------------
function theme_per_user_custom_theme_template($template = '') {
    if (isset($_COOKIE[THEME_PER_USER_OPTION])) {
        $template_data = json_decode(base64_decode(urldecode($_COOKIE[THEME_PER_USER_OPTION])), true);
        $template = isset($template_data['template']) ? $template_data['template'] : $template;
    }
    return $template;
}

function theme_per_user_custom_theme_style($stylesheet = '') {
    if (isset($_COOKIE[THEME_PER_USER_OPTION])) {
        $stylesheet_data = json_decode(base64_decode(urldecode($_COOKIE[THEME_PER_USER_OPTION])), true);
        $stylesheet = isset($stylesheet_data['stylesheet']) ? $stylesheet_data['stylesheet'] : $stylesheet;
    }
    return $stylesheet;
}

//------------------------------------------------------------------------------
//
// Set the new theme
//
function theme_per_user_change_theme() {
    add_filter('template', 'theme_per_user_custom_theme_template');
    add_filter('stylesheet', 'theme_per_user_custom_theme_style');
    add_filter('option_current_theme', 'theme_per_user_custom_theme_template');
    add_filter('option_template', 'theme_per_user_custom_theme_style');
    add_filter('option_stylesheet', 'theme_per_user_custom_theme_template');
}

add_action('plugins_loaded', 'theme_per_user_change_theme');

//------------------------------------------------------------------------------
//
// Set cookie
//
function theme_per_user_set_cookie($auth_cookie, $expire, $expiration, $user_id, $scheme) {
    $user_theme = get_user_meta($user_id, THEME_PER_USER_OPTION, true);

    if ('' < $user_theme)
        setcookie(THEME_PER_USER_OPTION, $user_theme, time() + 60 * 60 * 24 * 365, '/'); // expire in one year
    else
        setcookie(THEME_PER_USER_OPTION, '', time() - 1, '/'); // remove the cookie value
}

add_action('set_auth_cookie', 'theme_per_user_set_cookie', 10, 5);

//------------------------------------------------------------------------------
//
// This will show below the color scheme and above username field
//
function theme_per_user_extra_profile_fields($user) {
    $user_theme_data = json_decode(base64_decode(urldecode(get_user_meta($user->ID, THEME_PER_USER_OPTION, true))), true);
    $user_theme = isset($user_theme_data['stylesheet']) ? $user_theme_data['stylesheet'] : '';
    $user_theme_name = isset($user_theme_data['name']) ? $user_theme_data['name'] : '';

    if ('' == $user_theme) {
        $current_theme = wp_get_theme(); // get the current theme
        $user_theme_stylesheet = $current_theme->get_stylesheet();
        $user_theme_name = $current_theme->Name;
    }
    ?>
    <h3>Theme per user</h3>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="<?php print THEME_PER_USER_OPTION; ?>">Custom Theme</label></th>
            <td>
                <?php $all_themes = wp_get_themes(array('allowed' => true)); ?>
                <select id="<?php print THEME_PER_USER_OPTION; ?>" name="<?php print THEME_PER_USER_OPTION; ?>">
                    <option value="-1">Default theme</option>
                    <?php foreach ($all_themes as $theme) : ?>
                        <?php $selected = ''; if ($theme->get_stylesheet() == $user_theme and $theme->Name == $user_theme_name) { $selected = ' selected="selected"'; } ?>
                        <option <?php print $selected; ?> value="<?php echo urlencode(base64_encode(
                            json_encode(array('template' => $theme->Template, 'stylesheet' => $theme->get_stylesheet(), 'name' => $theme->Name))
                        )) ?>"><?php print $theme->Name . ' (' . $theme->Template . ')'; ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="description">Select the theme you want to be associated with your user.</span>
            </td>
        </tr>
        </tbody>
    </table>
    <?php
}

add_action('profile_personal_options', 'theme_per_user_extra_profile_fields');

//------------------------------------------------------------------------------
//
// save the extra field
//
function theme_per_user_save_extra_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) return false;

    $current_theme = wp_get_theme(); // get the current theme
    $current_theme = $current_theme->get_stylesheet();
    $post_value = $_POST[THEME_PER_USER_OPTION];
    $set_theme_data = json_decode(base64_decode(urldecode($post_value)), true);
    $set_theme = isset($set_theme_data['stylesheet']) ? $set_theme_data['stylesheet'] : '';
    if ($set_theme != $current_theme)
        update_user_meta($user_id, THEME_PER_USER_OPTION, $_POST[THEME_PER_USER_OPTION]);

    if (-1 == $_POST[THEME_PER_USER_OPTION])
        delete_user_meta($user_id, THEME_PER_USER_OPTION);
}

add_action('personal_options_update', 'theme_per_user_save_extra_profile_fields');
add_action('edit_user_profile_update', 'theme_per_user_save_extra_profile_fields');

//------------------------------------------------------------------------------
//
// Update the custom theme if the current user changes the theme
//
function theme_per_user_theme_activation_function() {
    $user_id = get_current_user_id();
    $user_theme_data = json_decode(base64_decode(urldecode(get_user_meta($user_id, THEME_PER_USER_OPTION, true))), true);
    $user_theme = isset($user_theme_data['template']) ? $user_theme_data['template'] : '';

    if ('' == $user_theme) {
        $theme = wp_get_theme(); // get the current theme
        $value = urlencode(base64_encode(json_encode(array('template' => $theme->Template, 'stylesheet' => $theme->get_stylesheet(), 'name' => $theme->Name))));
        update_user_meta($user_id, THEME_PER_USER_OPTION, $value);
    }
}

add_action('after_switch_theme', 'theme_per_user_theme_activation_function');

/* Cookie content serialized and encoded:
 *    array(  'template' => $theme->Template,
 *            'stylesheet' => $theme->get_stylesheet()
 *            )
 */

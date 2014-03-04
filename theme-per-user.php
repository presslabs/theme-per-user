<?php
/**
 * Plugin Name: Theme per user
 * Description: Load one Theme for a specific user. You must set the theme from `User Profile` then logout and login again in order to take effect.
 * Version:     1.0
 * Author:      PressLabs
 * Author URI:  http://www.presslabs.com/
 * License:     GPLv3
 */

define('THEME_PER_USER_OPTION', 'theme_per_user');

//------------------------------------------------------------------------------
function theme_per_user_custom_theme( $template = '' ) {
  if ( isset( $_COOKIE[ THEME_PER_USER_OPTION ] ) ) {
    $template = $_COOKIE[ THEME_PER_USER_OPTION ];
  }
  return $template;
}

//------------------------------------------------------------------------------
//
// Set the new theme
//
function theme_per_user_change_theme() {
  add_filter('template', 'theme_per_user_custom_theme');
  add_filter('stylesheet', 'theme_per_user_custom_theme');
  add_filter('option_current_theme', 'theme_per_user_custom_theme');
  add_filter('option_template', 'theme_per_user_custom_theme');
  add_filter('option_stylesheet', 'theme_per_user_custom_theme');
}
add_action('plugins_loaded', 'theme_per_user_change_theme');

//------------------------------------------------------------------------------
//
// Set cookie
//
function theme_per_user_set_cookie( $auth_cookie, $expire, $expiration, $user_id, $scheme ) {
  $user_theme = get_user_meta( $user_id, THEME_PER_USER_OPTION, true );

  if ( '' < $user_theme )
    setcookie( THEME_PER_USER_OPTION, $user_theme, time() + 60*60*24*365, '/' ); // expire in one year
  else
    setcookie( THEME_PER_USER_OPTION, '', time() -1, '/' ); // remove the cookie value
}
add_action('set_auth_cookie', 'theme_per_user_set_cookie', 10, 5);

//------------------------------------------------------------------------------
//
// This will show below the color scheme and above username field
//
function theme_per_user_extra_profile_fields( $user ) {
  $user_theme = esc_attr( get_user_meta( $user->ID, THEME_PER_USER_OPTION, true ) ); // $user contains WP_User object
  if ( '' == $user_theme ) {
    $current_theme = wp_get_theme(); // get the current theme
    $user_theme = $current_theme->Template;
  }
?>
  <h3>Theme per user</h3>
  <table class="form-table">
  <tbody>
  <tr>
    <th><label for="<?php print THEME_PER_USER_OPTION; ?>">Custom Theme</label></th>
    <td>
    <?php $all_themes = wp_get_themes( array( 'allowed' => true ) ); ?>
      <select id="<?php print THEME_PER_USER_OPTION; ?>" name="<?php print THEME_PER_USER_OPTION; ?>">
        <option value="-1">Default theme</option>
        <?php foreach ( $all_themes as $theme ) : ?>
        <?php $selected = ''; if ( $theme->Template == $user_theme ) $selected = ' selected="selected"'; ?>
        <option <?php print $selected; ?>value="<?php print $theme->Template; ?>"><?php print $theme->Name 
          . " (" . $theme->Template . ")"; ?></option>
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
function theme_per_user_save_extra_profile_fields( $user_id ) {
  if ( ! current_user_can( 'edit_user', $user_id ) ) return false;

  $current_theme = wp_get_theme(); // get the current theme
  $current_theme = $current_theme->Template;
  if ( $_POST[ THEME_PER_USER_OPTION ] != $current_theme )
    update_user_meta( $user_id, THEME_PER_USER_OPTION, $_POST[ THEME_PER_USER_OPTION ] );

  if ( -1 == $_POST[ THEME_PER_USER_OPTION ] )
    delete_user_meta( $user_id, THEME_PER_USER_OPTION );
}
add_action('personal_options_update', 'theme_per_user_save_extra_profile_fields');
add_action('edit_user_profile_update', 'theme_per_user_save_extra_profile_fields');

//------------------------------------------------------------------------------
//
// Update the custom theme if the current user change the theme
//
function theme_per_user_theme_activation_function() {
  $user_id = get_current_user_id();
  $user_theme = esc_attr( get_user_meta( $user_id, THEME_PER_USER_OPTION, true ) );

  if ( '' == $user_theme ) {
    $wp_theme = wp_get_theme(); // get the current theme
    $user_theme = $wp_theme->Template;
    update_user_meta( $user_id, THEME_PER_USER_OPTION, $user_theme );
  }
}
add_action('after_switch_theme', 'theme_per_user_theme_activation_function');

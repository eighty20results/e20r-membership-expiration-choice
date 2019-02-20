=== Eighty / 20 Results: Member Cancellation Policy for Paid Memberships Pro ===
Contributors: sjolshagen
Tags: pmpro, paid memberships pro, members, memberships, membership cancellation choice, membership cancellation policy
Requires at least: 4.7
Tested up to: 5.1
Stable tag: 1.8

Adds a membership level setting to configure how PMPro will handle member cancellations.

== Description ==

By default, PMPro terminates the user's access to protected content immediately. This plugin will configure the membership to end at the termination
of the currently paid-for membership period (new default). However, it's possible to change this policy to "immediately"
on a per-membership level basis, via the Membership Level settings in the WordPress backend.

== Installation ==

1. Upload the `e20r-member-cancellation-policy` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Edit your membership levels and set the Membership Cancellation Policy option for each level.

== Changelog == 

== 1.8 ==

* ENHANCEMENT: Updated copyright notice and now tested up to v5.1

== 1.7 ==

* ENHANCEMENT: Add CSS file containing style to hide Cancel link if member cancelled membership already
* ENHANCEMENT: Remove 'member cancelled this level' meta when membership is expired by Cron
* ENHANCEMENT: Conditionally load custom CSS to hide 'Cancel' link on Accounts page
* ENHANCEMENT: Track membership level being cancelled for the user (used by conditional CSS load)

== 1.6 ==

* ENHANCEMENT: Use cache for membership end date & user ID map
* ENHANCEMENT: Refactor licensing domain initiation
* ENHANCEMENT: Added membership_ends() function for user ID -> enddate
* BUG FIX: Prevent fatal error during plugin upgrades
* BUG FIX: Didn't calculate end date (for messages & display) correctly

== 1.5 ==

* ENHANCEMENT: Upgraded Utilities class (1.6)
* ENHANCEMENT: Added is_in_trial() method which includes support for Subscription Delays add-on & native trial periods
* BUG FIX: Could excessively nest when sanitizing array values

== 1.4 ==

* BUG FIX: Would not always load the correct membership record
* ENHANCEMENT: Avoid double-loading class on init
* ENHANCEMENT: Added filters to allow replacement of 'subscription' (singular) and 'subscriptions' (plural) strings (e20r-member-cancellation-subscription-label-singular and e20r-member-cancellation-subscription-label-plural filters)
* ENHANCEMENT: Allow singleton pattern for class (not required)
* ENHANCEMENT: Allow caching of Membership Level definition per user ID
* ENHANCEMENT: Renamed main class for plugin (WordPress Code style compliance & autoloader simplicity)
* ENHANCEMENT: Temporarily remove ability to configure deletion of inactive member accounts
* ENHANCEMENT: Use properly sanitized REQUEST variables
* ENHANCEMENT: Use autoloader for plugin
* ENHANCEMENT: Added debug statements to help isolate potential issues
* ENHANCEMENT: Add caching and utilities classes
* ENHANCEMENT: Add namespace for plugin
* ENHANCEMENT: Refactor namespace
* ENHANCEMENT: Use licensing logic in plugin
* ENHANCEMENT: Added placeholder for option to delete inactive (expired members) users from system
* ENHANCEMENT: Use plugin slug constant for translations
* ENHANCEMENT: Initial work to support trial periods and setting the enddate for the membership if cancelled to the last day of the trial.
* ENHANCEMENT: Add autoloader function
* ENHANCEMENT: Add new class directory to build script

== 1.3 ==

* ENHANCEMENT/FIX: Change the Account 'Expiration' header to 'Ends' if the policy is 'end of period' and the user's membership has an enddate

== 1.2 ==

* ENHANCEMENT/FIX: Only load policy engine if user is logged in
* ENHANCEMENT/FIX: Load the pmpro action hooks if the policy is set to end of period for that user's level
* BUG/FIX: Would incorrectly exit for recurring memberships

== 1.1 ==

* ENHANCEMENT/FIX: Handle class instantiation

== 1.0 ==

* BUG/FIX: Incorrect build process for change logs
* BUG/FIX: Build environment tweaks
* ENHANCEMENT/FIX: Add license header & fix css elements for add-on
* ENHANCEMENT/FIX: Add styling for the level settings page
* ENHANCEMENT/FIX: Use correct setting name for termination policy choice
* ENHANCEMENT/FIX: Set the default value for the current level while saving
* Initial Commit


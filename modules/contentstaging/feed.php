<?php
/**
* View used to display one feed (or all of them together), and sync it
* Supports pagination
*
* @todo add functionality to sync complete feed (all events), not just X events (either here or in feeds view)
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*
*/

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$tpl = eZTemplate::factory();
$targetId = $Params['target_id'];

//$ini = eZINI::instance();
//$serviceIni = eZINI::instance( 'contentstaging.ini' );

/*$user = eZUser::currentUser();
if ( !$user->isLoggedIn() )
    return $Module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
$userID = $user->id();*/

if ( $module->isCurrentAction( 'SyncEvents' ) )
{
    /// @todo: test if current user has access to contentstaging/sync, as access
    ///        to the view is only limited by 'view'

    $syncErrors = array();
    $syncResults = array();
    if ( $http->hasPostVariable( 'syncArray' ) && is_array( $http->postVariable( 'syncArray' ) ) )
    {
        $tosync = array();
        foreach ( $http->postVariable( 'syncArray' ) as $eventId )
        {
            $event = eZContentStagingEvent::fetch( $eventId );
            /// @todo with finer grained perms, we should check user can sync these items, one by one
            if ( $event instanceof eZContentStagingEvent )
            {
                $tosync[$event->attribute( 'id' )] = $event;
            }
            else
            {
                eZDebug::writeError( "Invalid event id received for syncing: $eventId", 'contentstaging/feed' );
            }
        }
        // we sync by sorting based on event IDs to keep proper history
        ksort( $tosync );
        $out = eZContentStagingEvent::syncEvents( $tosync );
        /// @todo apply i18n to messages
        foreach( $out as $id => $resultCode )
        {
            $event = $tosync[$id];
            if ( $resultCode !== 0 )
            {
                $syncErrors[] = " Object " . $event->attribute( 'object_id' ) . " to be synchronised to feed " . $event->attribute( 'target_id' ) . ": failure ($resultCode) [Event $id]";
            }
            else
            {
                $syncResults[] = "Object " . $event->attribute( 'object_id' ) . " succesfully synchronised to feed " . $event->attribute( 'target_id' ) . " [Event $id]";
            }
        }

    }
    else
    {
        eZDebug::writeError( "No list of events to be syncronised received. Pen testing? tsk tsk tsk", __METHOD__ );
        /// @todo apply i18n to message
        $syncErrors[] = "No object to sync...";
    }
    /// @todo decide format for these 2 variables: let translation happen here or in tpl?
    $tpl->setVariable( 'sync_errors', $syncErrors );
    $tpl->setVariable( 'sync_results', $syncResults );

} // end of 'doing sync' action

if ( $targetId !== null )
{
    /// @todo check that target exists (either here or in tpl code)
}

/// @todo !important fetch list of items to be displayed here, not purely in template

$tpl->setVariable( 'target_id', $targetId );
$tpl->setVariable( 'view_parameters', array( 'offset', (int)$Params['Offset'] ) );

$Result = array();
$Result['content'] = $tpl->fetch( 'design:contentstaging/feed.tpl' );
$Result['path'] = array( array( 'text' => ezpI18n::tr( 'staging', 'Content synchronization' ),
                                'url' => 'contentstaging/feeds' ) );
if ( $targetId == null )
{
    $Result['path'][] = array( 'text' => ezpI18n::tr( 'staging', 'All feeds' ),
                               'url' => false );
}
else
{
    /// @todo use the name of the feed, not its id
    $Result['path'][] = array( 'text' => ezpI18n::tr( 'staging', "Feed: $targetId" ),
                               'url' => false );
}

?>
<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Activitytab_Page_AJAX {

  static function getContactActivity() {
    $contactID = CRM_Utils_Type::escape($_POST['contact_id'], 'Integer');
    $context = CRM_Utils_Type::escape(CRM_Utils_Array::value('context', $_GET), 'String');

    $sortMapper = array(
      0 => 'activity_type',
      1 => 'subject',
      2 => 'source_contact_name',
      3 => '',
      4 => 'activity_date_time',
      5 => 'status_id',
    );

    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';

    $params = $_POST;
    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $params['contact_id'] = $contactID;
    $params['context'] = $context;

    $params['include_source_contact'] = CRM_Utils_Request::retrieve('include_source_contact', 'Integer');

    // get the contact activities
    $activities = CRM_Activitytab_BAO_Activity::getContactActivitySelector($params);

    // store the activity filter preference CRM-11761
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    if ($userID) {
      //flush cache before setting filter to account for global cache (memcache)
      $domainID = CRM_Core_Config::domainID();
      $cacheKey = CRM_Core_BAO_Setting::inCache(
        CRM_Core_BAO_Setting::PERSONAL_PREFERENCES_NAME,
        'activity_tab_filter',
        NULL,
        $userID,
        TRUE,
        $domainID,
        TRUE
      );
      if ( $cacheKey ) {
        CRM_Core_BAO_Setting::flushCache($cacheKey);
      }

      $activityFilter = array(
        'activity_type_filter_id' => empty($params['activity_type_id']) ? '' :
          CRM_Utils_Type::escape($params['activity_type_id'], 'Integer'),
        'activity_type_exclude_filter_id' => empty($params['activity_type_exclude_id']) ? '' :
          CRM_Utils_Type::escape($params['activity_type_exclude_id'], 'Integer'),
      );

      CRM_Core_BAO_Setting::setItem(
        $activityFilter,
        CRM_Core_BAO_Setting::PERSONAL_PREFERENCES_NAME,
        'activity_tab_filter',
        NULL,
        $userID,
        $userID
      );
    }

    $iFilteredTotal = $iTotal = $params['total'];
    $selectorElements = array(
      'activity_type', 'subject', 'source_contact',
      'target_contact', 'assignee_contact',
      'activity_date', 'status','links', 'class',
    );

    echo CRM_Utils_JSON::encodeDataTableSelector($activities, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

}
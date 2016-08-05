<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldSavedSearch;
$cidReset = true;

require_once 'main/inc/global.inc.php';

api_block_anonymous_users();

$userId = api_get_user_id();
$userInfo = api_get_user_info();

$em = Database::getManager();

$form = new FormValidator('search', 'post', api_get_self());
$form->addHeader(get_lang('Diagnosis'));

/** @var ExtraFieldSavedSearch  $saved */
$search = [
    'user' => $userId
];

$items = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findBy($search);

$extraFieldSession = new ExtraField('session');
$extraFieldValueSession = new ExtraFieldValue('session');
//$extra = $extraField->addElements($form, '', [], true, true);

/*$extra = $extraField->addElements($form, '', [], true, true, array('heures_disponibilite_par_semaine'));
$elements = $form->getElements();
$variables = ['theme', 'domaine', 'competenceniveau', 'filiere'];
foreach ($elements as $element) {
    $element->setAttribute('extra_label_class', 'red_underline');
}

$htmlHeadXtra[] ='<script>
$(document).ready(function(){
	'.$extra['jquery_ready_content'].'
});
</script>';*/


$extraFieldValue = new ExtraFieldValue('user');
$wantStage = $extraFieldValue->get_values_by_handler_and_field_variable(api_get_user_id(), 'filiere_want_stage');
$hide = true;
if ($wantStage !== false) {
    $hide = $wantStage['value'] === 'yes';
}

$defaultValueStatus = 'extraFiliere.hide()';
if ($hide === false) {
    $defaultValueStatus = '';
}

$url = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?a=order&user_id='.$userId;

$htmlHeadXtra[] ='<script>
$(document).ready(function() {

    var extraFiliere = $("input[name=\'extra_filiere[extra_filiere]\']").parent().parent().parent().parent();
    
    '.$defaultValueStatus.'
    
    $("input[name=\'extra_filiere_want_stage[extra_filiere_want_stage]\']").change(function() {
        if ($(this).val() == "no") {
            extraFiliere.show();
        } else {
            extraFiliere.hide();
        }
    });
        
    $("#extra_domaine").parent().append(
        $("<a>", {
            "class": "btn ajax btn-default",
            "href": "'.$url.'&field_variable=extra_domaine",
            "text": "'.get_lang('Order').'"             
        })
    );    
    
    $("#extra_theme").parent().append(
        $("<a>", {
            "class": "btn ajax btn-default",
            "href": "'.$url.'&field_variable=extra_theme",
            "text": "'.get_lang('Order').'"             
        })
    );
    
    $("#extra_domaine").on("change", function() {
        var domainList = [];
        $( "#extra_domaine option:selected" ).each(function() {       
            domainList.push($(this).val());
        });
        
        var domainListToString = JSON.stringify(domainList);        
        $.ajax({
            contentType: "application/x-www-form-urlencoded",
            type: "GET",
            url: "'.api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?a=search_options_from_tags&type=session&from=extra_domaine&search=extra_theme&options="+domainListToString,
            success: function(data) {            
                $("#extra_theme").find("option").remove().end();                    
                $("#extra_theme").empty();
                var selectToString = "";
                jQuery.each(JSON.parse(data), function(i, item) {
                   selectToString += "<optgroup label=\'"+item.text+"\'>";                   
                   jQuery.each(item.children, function(j, data) {
                        console.log(data);
                        if (data.text != "") {                                    
                            selectToString += "<option value=\'"+data.text+"\'> " +data.text+"</option>"
                        }
                    });                         
                    selectToString += "</optgroup>";
                });        
                
                $("#extra_theme").html(selectToString);                 
                $("#extra_theme").selectpicker("refresh");
            }           
            
         });        
    });
});
</script>';


$form->addButtonSave(get_lang('Save'), 'save');

$result = SessionManager::getGridColumns('simple');
$columns = $result['columns'];
$column_model = $result['column_model'];

$defaults = [];
$tagsData = [];
if (!empty($items)) {
    /** @var ExtraFieldSavedSearch $item */
    foreach ($items as $item) {
        $variable = 'extra_'.$item->getField()->getVariable();
        if ($item->getField()->getFieldType() == Extrafield::FIELD_TYPE_TAG) {
            $tagsData[$variable] = $item->getValue();
        }
        $defaults[$variable] = $item->getValue();
    }
}

$form->setDefaults($defaults);

//$view = $form->returnForm();
$filterToSend = '';

if ($form->validate()) {
    $params = $form->getSubmitValues();
    /** @var \Chamilo\UserBundle\Entity\User $user */
    $user = $em->getRepository('ChamiloUserBundle:User')->find($userId);

    if (isset($params['save'])) {
        MessageManager::send_message_simple(
            $userId,
            get_lang('DiagnosisFilledSubject'),
            get_lang('DiagnosisFilledDescription')
        );

        $drhList = UserManager::getDrhListFromUser($userId);
        if ($drhList) {
            foreach ($drhList as $drhId) {
                $subject = sprintf(get_lang('UserXHasFilledTheDiagnosis'), $userInfo['complete_name']);
                $content = sprintf(get_lang('UserXHasFilledTheDiagnosisDescription'), $userInfo['complete_name']);
                MessageManager::send_message_simple($drhId, $subject, $content);
            }
        }

        Display::addFlash(Display::return_message(get_lang('Saved')));
        header("Location: ".api_get_self());
        exit;
    } else {
        // Search
        $filters = [];
        // Parse params.
        foreach ($params as $key => $value) {
            if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
                continue;
            }
            if (!empty($value)) {
                $filters[$key] = $value;
            }
        }

        $filterToSend = [];
        if (!empty($filters)) {
            $filterToSend = ['groupOp' => 'AND'];
            if ($filters) {
                $count = 1;
                $countExtraField = 1;
                foreach ($result['column_model'] as $column) {
                    if ($count > 5) {
                        if (isset($filters[$column['name']])) {
                            $defaultValues['jqg'.$countExtraField] = $filters[$column['name']];
                            $filterToSend['rules'][] = ['field' => $column['name'], 'op' => 'cn', 'data' => $filters[$column['name']]];
                        }
                        $countExtraField++;
                    }
                    $count++;
                }
            }
        }
    }
}

$extraField = new ExtraField('user');
$userForm = new FormValidator('user_form', 'post', api_get_self());
$jqueryExtra = '';

$htmlHeadXtra[] ='<script>
$(document).ready(function() {
	$("#filiere_panel").hide();	
    $("#dispo_panel").hide();    
    $("#dispo_pendant_panel").hide();
    $("#niveau_panel").hide();
    $("#methode_panel").hide();
    $("#themes_panel").hide();    
    $("#objectifs_panel").hide();

	$("#filiere").on("click", function() {
	    $("#filiere_panel").toggle();
	    return false;
	});
	
	$("#dispo").on("click", function() {
	    $("#dispo_panel").toggle();
	    return false;
	});
	
	$("#dispo_pendant").on("click", function() {
	    $("#dispo_pendant_panel").toggle();
	    return false;
	});	
	
	$("#niveau").on("click", function() {
	    $("#niveau_panel").toggle();
	    return false;
	});
	
	$("#methode").on("click", function() {
	    $("#methode_panel").toggle();
	    return false;
	});
	
	$("#themes").on("click", function() {
	    $("#themes_panel").toggle();
	    return false;
	});
	
	$("#objectifs").on("click", function() {
	    $("#objectifs_panel").toggle();
	    return false;
	});
});
</script>';

$panel = Display::panel(get_lang('FiliereExplanation'), '', '', '',  '', 'filiere_panel');
$userForm->addHeader(Display::url(get_lang('Filiere'), '#', ['id'=> 'filiere']).''.$panel);
$fieldsToShow = [
    'statusocial',
    'filiere_user',
    'filiereprecision',
    'filiere_want_stage',
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow
);

$jqueryExtra .= $extra['jquery_ready_content'];

$fieldsToShow = [
    'filiere'
];

$extra = $extraFieldSession->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow
);


$jqueryExtra .= $extra['jquery_ready_content'];

$panel = Display::panel(get_lang('DisponibiliteAvantExplanation'), '', '', '',  '', 'dispo_panel');
$userForm->addHeader(Display::url(get_lang('DisponibiliteAvant'), '#', ['id'=> 'dispo']).''.$panel);

$extra = $extraFieldSession->addElements(
    $userForm,
    '',
    [],
    true,
    true,
    array('access_start_date', 'access_end_date')
);
$jqueryExtra .= $extra['jquery_ready_content'];

$elements = $userForm->getElements();
$variables = ['access_start_date', 'access_end_date'];
foreach ($elements as $element) {
    $element->setAttribute('extra_label_class', 'red_underline');
}

$fieldsToShow = [
    'heures_disponibilite_par_semaine',
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow
);

$jqueryExtra .= $extra['jquery_ready_content'];

$panel = Display::panel(get_lang('DisponibilitePendantMonStageExplanation'), '', '', '',  '', 'dispo_pendant_panel');
$userForm->addHeader(Display::url(get_lang('DisponibilitePendantMonStage'), '#', ['id'=> 'dispo_pendant']).''.$panel);

$fieldsToShow = [
    'datedebutstage',
    'datefinstage',
    'poursuiteapprentissagestage',
    'heures_disponibilite_par_semaine_stage'
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow
);

$jqueryExtra .= $extra['jquery_ready_content'];

$panel = Display::panel(get_lang('ThemesObjectifsExplanation'), '', '', '',  '', 'themes_panel');
$userForm->addHeader(Display::url(get_lang('ThemesObjectifs'), '#', ['id'=> 'themes']).''.$panel);

$fieldsToShow = [
    'domaine',
    'theme'
];

$specialUrlList = [
    'theme' => api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?a=search_tags_from_diagnosis'
];

$extra = $extraFieldSession->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow,
    $defaults,
    $specialUrlList,
    true
);

$jqueryExtra .= $extra['jquery_ready_content'];

$panel = Display::panel(get_lang('NiveauLangueExplanation'), '', '', '',  '', 'niveau_panel');
$userForm->addHeader(Display::url(get_lang('NiveauLangue'), '#', ['id'=> 'niveau']).''.$panel);

$fieldsToShow = [
    //'competenceniveau'
    'ecouter',
    'lire',
    'participer_a_une_conversation',
    's_exprimer_oralement_en_continu',
    'ecrire'
];

$extra = $extraFieldSession->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow,
    $defaults
);

$jqueryExtra .= $extra['jquery_ready_content'];

$panel = Display::panel(get_lang('ObjectifsApprentissageExplanation'), '', '', '',  '', 'objectifs_panel');
$userForm->addHeader(Display::url(get_lang('ObjectifsApprentissage'), '#', ['id'=> 'objectifs']).''.$panel);

$fieldsToShow = [
    'objectif_apprentissage'
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    false,
    $fieldsToShow,
    $fieldsToShow
);

$jqueryExtra .= $extra['jquery_ready_content'];

$panel = Display::panel(get_lang('MethodeTravailExplanation'), '', '', '',  '', 'methode_panel');
$userForm->addHeader(Display::url(get_lang('MethodeTravail'), '#', ['id'=> 'methode']).''.$panel);

$fieldsToShow = [
    'methode_de_travaille',
    'accompagnement'
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow
);

$jqueryExtra .= $extra['jquery_ready_content'];

$htmlHeadXtra[] ='<script>
$(document).ready(function(){
	'.$jqueryExtra.'
});
</script>';

$userForm->addButtonSave(get_lang('Save'));

$userForm->setDefaults($defaults);
$userFormToString = $userForm->returnForm();

if ($userForm->validate()) {
    // Saving to user extra fields
    $extraFieldValue = new ExtraFieldValue('user');
    $userData = $userForm->getSubmitValues();
    $extraFieldValue->saveFieldValues($userData);

    // Saving to extra_field_saved_search

    /** @var \Chamilo\UserBundle\Entity\User $user */
    $user = $em->getRepository('ChamiloUserBundle:User')->find($userId);

    $sessionFields = [
        'extra_access_start_date',
        'extra_access_end_date',
        'extra_filiere',
        'extra_domaine',
        'extra_temps-de-travail',
        //'extra_competenceniveau',
        'extra_theme',
        'extra_ecouter',
        'extra_lire',
        'extra_participer_a_une_conversation',
        'extra_s_exprimer_oralement_en_continu',
        'extra_ecrire'
    ];

    foreach ($userData as $key => $value) {
        $found = strpos($key, '__persist__');

        if ($found === false) {
            continue;
        }

        $tempKey = str_replace('__persist__', '', $key);
        if (!isset($params[$tempKey])) {
            $params[$tempKey] = array();
        }
    }

    if (isset($userData['extra_filiere_want_stage']) &&
        isset($userData['extra_filiere_want_stage']['extra_filiere_want_stage'])
    ) {
        $wantStage = $userData['extra_filiere_want_stage']['extra_filiere_want_stage'];

        if ($wantStage === 'yes') {
            if (isset($userData['extra_filiere_user'])) {
                $userData['extra_filiere'] = [];
                $userData['extra_filiere']['extra_filiere'] = $userData['extra_filiere_user']['extra_filiere_user'];
            }
        }
    }

    // save in ExtraFieldSavedSearch.
    foreach ($userData as $key => $value) {
        if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
            continue;
        }

        if (!in_array($key, $sessionFields)) {
            continue;
        }

        $field_variable = substr($key, 6);
        $extraFieldInfo = $extraFieldValueSession
            ->getExtraField()
            ->get_handler_field_info_by_field_variable($field_variable);

        if (!$extraFieldInfo) {
            continue;
        }

        $extraFieldObj = $em->getRepository('ChamiloCoreBundle:ExtraField')->find($extraFieldInfo['id']);

        $search = [
            'field' => $extraFieldObj,
            'user' => $user
        ];

        /** @var ExtraFieldSavedSearch  $saved */
        $saved = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findOneBy($search);

        if ($saved) {
            $saved
                ->setField($extraFieldObj)
                ->setUser($user)
                ->setValue($value)
            ;
            $em->merge($saved);
        } else {
            $saved = new ExtraFieldSavedSearch();
            $saved
                ->setField($extraFieldObj)
                ->setUser($user)
                ->setValue($value)
            ;
            $em->persist($saved);
        }
        $em->flush();
    }
    Display::addFlash(Display::return_message(get_lang('Saved')));
    header('Location:'.api_get_self());
    exit;
}

$tpl = new Template(get_lang('Diagnosis'));
$tpl->assign('form', $userFormToString);
$content = $tpl->fetch('default/user_portal/search_extra_field.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();

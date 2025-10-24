<?php
/*
 * 
 * page labels work as follows 
 * function_level_actionNo
 * 
 * A page can have sections that are selected by JQM and defined in js
 * 
 * function = broad area of operation
 * 0 = default site registrationViews
 * 1 = admin
 * 2 = system options
 * 3 = review
 * 4 = step1 runtime
 * 5 = step2 runtime
 * 6 = step4 runtime
 * 7 = surveys/forms runtime
 * 8 = analysis and downloads
 * 
 * level = depth into IA
 * 0 = primary hub (e.g. admin hub, user-profile, runtime)
 * 1 = configuration, summary or listing page (e.g list of experiments, list of reviewed datasets)
 * 2 = detail page (specific item chosen from level 1
 * 3 = sub-detail page (e.g. step1 users list)
 * 
 * actionNo = index within this function_level combination (0 means multi-section page)
 * 
 */


$pageLabelMappings = array(
  
  // <editor-fold defaultstate="collapsed" desc=" level 0 definitions used in JQM">

  array(
    'pageLabel' => '0_0_0', 
    'pageTitle' => 'imgame homepage',
    'header' => null,
    'main' => "views/staticPages/home.html"
  ),
  array(
    'pageLabel' => '0_0_1', 
    'pageTitle' => 'imgame error page',
    'header' => null,
    'main' => "views/staticPages/error.html"
  ),
	array(
		'pageLabel' => '0_0_2',
		'header' => null,
		'main' => "views/staticPages/loginViews/loginRetry.html"
	),
	array(
		'pageLabel' => '0_0_3',
		'header' => null,
		'main' => "views/staticPages/loginViews/passwordReset.html"
	),
	array(
		'pageLabel' => '0_0_4',
		'header' => null,
		'main' => "views/staticPages/loginViews/emailReset.html"
	),
	array(
		'pageLabel' => '0_0_5',
		'header' => null,
		'main' => "views/staticPages/loginViews/resetTooShort.html"
	),
	array(
		'pageLabel' => '0_0_6',
		'header' => null,
		'main' => "views/staticPages/loginViews/resetMismatch.html"
	),
	array(
		'pageLabel' => '0_0_7',
		'header' => null,
		'main' => "views/staticPages/loginViews/passwordIneligible.html"
	),
	array(
		'pageLabel' => '0_0_8',
		'header' => null,
		'main' => "views/staticPages/loginViews/emailExists.html"
	),
	array(
		'pageLabel' => '0_0_9',
		'header' => null,
		'main' => "views/staticPages/loginViews/emailResetIncorrectPassword.html"
	),
	array(
		'pageLabel' => '0_0_10',
		'header' => null,
		'main' => "views/staticPages/loginViews/passwordResetRequestSent.html"
	),
	array(
		'pageLabel' => '0_0_11',
		'header' => null,
		'main' => "views/staticPages/loginViews/passwordResetEmailNotSent.html"
	),
	array(
		'pageLabel' => '0_0_12',
		'header' => null,
		'main' => "views/staticPages/loginViews/passwordResetUnknownOperation.html"
	),
	array(
		'pageLabel' => '0_0_13',
		'header' => "views/staticPages/loginViews/passwordResetFormHeader.html",
		'main' => "views/staticPages/loginViews/passwordResetFormMain.html"
	),
	array(
		'pageLabel' => '0_0_14',
		'header' => null,
		'main' => "views/staticPages/loginViews/passwordResetUnauthenticated.html"
	),
	array(
		'pageLabel' => '0_0_15',
		'header' => null,
		'main' => "views/staticPages/loginViews/passwordResetSuccess.html"
	),
	array(
		'pageLabel' => '0_0_16',
		'header' => null,
		'main' => "views/staticPages/loginViews/passwordResetFail.html"
	),
	array(
    'pageLabel' => '1_0_1', 
    'pageTitle' => 'administration hub',
    'header' => "views/admin/adminHubHeader.html",
    'main' => "views/admin/adminHubMain.html"    
  ),
  array(
    'pageLabel' => '4_0_1', 
    'header' => "views/step1/step1authenticatedHeader.html",
    'main' => "views/step1/step1authenticatedMain.html"    
  ),
  array(
    'pageLabel' => '4_0_2', 
    'header' => "views/step1/step1authenticatedHeader.html",
    'main' => "views/step1/step1authenticatedMain.html"    
  ),
  array(
    'pageLabel' => '5_0_2', 
    'header' => "views/step2/step2RuntimeHeader.html",
    'main' => "views/step2/step2RuntimeFooter.html"   
  ),
  array(
    'pageLabel' => '5_0_3', 
    'header' => "views/step2inverted/iStep2RuntimeHeader.html",
    'main' => "views/step2inverted/iStep2RuntimeFooter.html"   
  ),
  array(
    'pageLabel' => '6_0_1', 
    'header' => "views/step4/step4HoldingHeader.html",
    'main' => "views/step4/step4HoldingMain.html"   
  ),
  array(
    'pageLabel' => '6_0_2', 
    'header' => "views/step4/step4RuntimeHeader.html",
    'main' => "views/step4/step4RuntimeMain.html"   
  ),  
  array(
    'pageLabel' => '6_0_3', 
    'header' => "views/step4/neStep4RuntimeHeader.html",
    'main' => "views/step4/neStep4RuntimeMain.html"   
  ),  
  array(
    'pageLabel' => '6_0_4', 
    'header' => "views/step4/leStep4RuntimeHeader.html",
    'main' => "views/step4/leStep4RuntimeMain.html"   
  ),  
  // 605 is special case for linked experiment
  array(
    'pageLabel' => '6_0_5', 
    'header' => "views/step4/tbtStep4RuntimeHeader.html",
    'main' => "views/step4/tbtStep4RuntimeMain.html"   
  ), 
  // 606 is a generic turn by turn Step4
  array(
    'pageLabel' => '6_0_6', 
    'header' => "views/step4/step4TBTRuntimeHeader.html",
    'main' => "views/step4/step4TBTRuntimeMain.html"   
  ),  
  array(
    'pageLabel' => '7_0_1', 
    'header' => "views/forms/stepFormRuntimeHeader.html",
    'main' => "views/forms/stepFormRuntimeFooter.html"   
  ),
  
  // </editor-fold >

  // <editor-fold defaultstate="collapsed" desc=" level 1 definitions used in JQM">

	array(
		'pageLabel' => '1_1_1',
		'pageTitle' => 'experiment configuration',
		'header' => "views/admin/exptConfigureHeader.html",
		'main' => "views/admin/exptConfigureMain.html"
	),

	// note: page 1_1_2 removed
	array(
		'pageLabel' => '1_1_3',
		'pageTitle' => 'research group management',
		'header' => "views/admin/rgManageHeader.html",
		'main' => "views/admin/rgManageMain.html"
	),
	array(
		'pageLabel' => '1_1_4',
		'pageTitle' => 'user permissions',
		'header' => "views/admin/usersManageHeader.html",
		'main' => "views/admin/usersManageMain.html"
	),
	array(
		'pageLabel' => '1_1_5',
		'pageTitle' => 'locations configuration',
		'header' => "views/admin/locationsConfigureHeader.html",
		'main' => "views/admin/locationsConfigureMain.html"
	),
	array(
		'pageLabel' => '1_1_6',
		'pageTitle' => 'subject configuration',
		'header' => "views/admin/topicsConfigureHeader.html",
		'main' => "views/admin/topicsConfigureMain.html"
	),

  // </editor-fold >
  
  // <editor-fold defaultstate="collapsed" desc=" level 2 definitions used in JQM">
  
  array(
    'pageLabel' => '1_2_0',    
    'pageTitle' => 'experiment & data clone',
    'header' => "views/admin/exptDataCloneHeader.html", // not yet implemented
    'main' => "views/admin/exptDataCloneMain.html"  
  ),
  array(
    'pageLabel' => '1_2_1',    // expt specific configuration or operation section
    'pageTitle' => 'e.g. experiment overview, but set in the back end depending on sectionNo',
    'header' => "views/admin/exptSectionHeader.html",
    'main' => "views/admin/exptSectionMain.html"  
  ),
  array(
    'pageLabel' => '1_2_2',    
    'pageTitle' => 'experiment PFC',
    'header' => "views/admin/xxxxHeader.html",
    'main' => "views/admin/xxxxMain.html"  
  ),  
  array(
    'pageLabel' => '4_2_0', 
    'header' => "views/step1/step1ControlHeader.html",
    'main' => "views/step1/step1ControlMain.html"    
  ),
  array(
    'pageLabel' => '4_2_1', 
    'header' => "views/step1/step1MonitorHeader.html",
    'main' => "views/step1/step1MonitorMain.html"   
  ),
  array(
    'pageLabel' => '7_2_1', 
    'header' => "views/forms/ineligibleHeader.html",
    'main' => "views/forms/ineligibleMain.html"  
  ),

  // </editor-fold >
  
  // <editor-fold defaultstate="collapsed" desc=" level 3 definitions used in JQM">
  
  array(
    'pageLabel' => '1_3_0',     
    'pageTitle' => 'step1 user details',
    'header' => "views/admin/listStep1UsersHeader.html",
    'main' => "views/admin/listStep1UsersMain.html"  
  ),
  array(
    'pageLabel' => '1_3_1', 
    'header' => "views/admin/stepFormCloneHeaderJQM.html",
    'main' => "views/admin/stepFormCloneMainJQM.html"
  ),
  array(
    'pageLabel' => '1_3_2', 
    'header' => "views/admin/stepFormConfigureHeaderJQM.html",
    'main' => "views/admin/stepFormConfigureMainJQM.html"
  ),
  array(
    'pageLabel' => '1_3_4',    
    'pageTitle' => 'step3 shuffle results',
    'header' => "views/step3/shuffleStatusHeader.html",
    'main' => "views/step3/shuffleStatusMain.html"  
  ),
  array(
    'pageLabel' => '1_3_5', 	
    'pageTitle' => 'snow shuffle results for null experiment',
    'header' => "views/step3/snowShuffleStatusHeader.html",   
    'main' => "views/step3/snowShuffleStatusMain.html"         
  ),
  array(
    'pageLabel' => '1_3_6', 	
    'pageTitle' => 'le-shuffle results for linked experiment',
    'header' => "views/step3/leShuffleStatusHeader.html",   
    'main' => "views/step3/leShuffleStatusMain.html"         
  ),
  array(
    'pageLabel' => '1_3_7', 	
    'pageTitle' => 'reflexive results for (tbt-linked) experiment',
    'header' => "views/step3/tbtShuffleStatusHeader.html",   
    'main' => "views/step3/tbtShuffleStatusMain.html"         
  ),
  array(
    'pageLabel' => '3_3_0', 
    'header' => "views/forms/surveyDataViewHeader.html",
    'main' => "views/forms/surveyDataViewMain.html"  
  ),
  array(
    'pageLabel' => '3_3_1', 
    'header' => "views/step1/step1ReviewHeader.html",
    'main' => "views/step1/step1ReviewMain.html"   
  ),  
  array(
    'pageLabel' => '5_3_0', 
    'header' => "views/step2/step2MonitorDetailHeader.html",
    'main' => "views/step2/step2MonitorDetailMain.html"  
  ),
  array(
    'pageLabel' => '5_3_1', 
    'header' => "views/step2/step2ReviewHeader.html",
    'main' => "views/step2/step2ReviewMain.html"  
  ),
  array(
    'pageLabel' => '5_3_2', 
    'header' => "views/step2inverted/iStep2MonitorDetailHeader.html",
    'main' => "views/step2inverted/iStep2MonitorDetailMain.html"  
  ),
  array(
    'pageLabel' => '5_3_3', 
    'header' => "views/step2inverted/iStep2ReviewHeader.html",
    'main' => "views/step2inverted/iStep2ReviewMain.html"  
  ),
  array(
    'pageLabel' => '6_3_0', 
    'header' => "views/step4/step4MonitorDetailHeader.html",
    'main' => "views/step4/step4MonitorDetailMain.html"  
  ),
  array(
    'pageLabel' => '6_3_1', 
    'header' => "views/step4/neMonitorDetailHeader.html",
    'main' => "views/step4/neMonitorDetailMain.html"  
  ),
  array(
    'pageLabel' => '6_3_2', 
    'header' => "views/step4/leMonitorDetailHeader.html",
    'main' => "views/step4/leMonitorDetailMain.html"  
  ),
  array(
    'pageLabel' => '6_3_3', 
    'header' => "views/step4/tbtMonitorDetailHeader.html",
    'main' => "views/step4/tbtMonitorDetailMain.html"  
  ),
	array(
		'pageLabel' => '7_3_1',
		'header' => "views/forms/surveyDataViewHeader.html",
		'main' => "views/forms/surveyDataViewMain.html"
	),
  array(
    'pageLabel' => '8_3_0', 
    'header' => "views/step1/step1RawDataHeader.html",
    'main' => "views/step1/step1RawDataMain.html"  
  ),
  array(
    'pageLabel' => '8_3_1', 
    'header' => "views/step2/step2QSRespondentsHeader.html",
    'main' => "views/step2/step2QSRespondentsMain.html"  
  ),
  array(
    'pageLabel' => '8_3_2', 
    'header' => "views/step2inverted/iStep2QSRespondentsHeader.html",
    'main' => "views/step2inverted/iStep2QSRespondentsMain.html"  
  ),
  array(
    'pageLabel' => '8_3_3', 
    'header' => "views/audit/arS2DetailHeader.html",
    'main' => "views/audit/arS2DetailMain.html"  
  ),
  array(
    'pageLabel' => '8_3_4', 
    'header' => "views/tools/qSetsQuantHeader.html",
    'main' => "views/tools/qSetsQuantMain.html"
  ),
  array(
    'pageLabel' => '8_3_5', 
    'header' => "views/tools/qSetsQualHeader.html",
    'main' => "views/tools/qSetsQualMain.html"
  ),
  array(
    'pageLabel' => '8_3_6', 
    'header' => "views/tools/neQSetsQuantHeader.html",
    'main' => "views/tools/neQSetsQuantMain.html"  
  ),
	array(
		'pageLabel' => '8_3_7',
		'header' => "views/tools/neQSetsQualHeader.html",
		'main' => "views/tools/neQSetsQualMain.html"
	),
	array(
		'pageLabel' => '8_3_8_0',
		'header' => "views/classic/s1QuantHeader.html",
		'main' => "views/classic/s1QuantMain.html"
	),
	array(
		'pageLabel' => '8_3_8_1',
		'header' => "views/classic/s1QualHeader.html",
		'main' => "views/classic/s1QualMain.html"
	),

  // </editor-fold >

	// <editor-fold defaultstate="collapsed" desc=" level 9 definitions - normally ad-hoc things">
	array(
		'pageLabel' => '9_9_9',
		'header' => "views/step1pre/step1preHeader.html",
		'main' => "views/step1pre/step1preMain.html"
	),


	// </editor-fold >

);


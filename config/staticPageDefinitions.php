<?php
/*
 * this maps all the static registrationViews which may be assembled by index.php to
 * the actual filepaths, for direct include
 * note, the index is implicit, i.e $staticPageMappings[0] returns views/home.html
 */
$staticPageMappings=array (
  "views/staticPages/home.html",                                      //  0
  "views/staticPages/loginViews/registerFail.html",                   //  1    //not used
  "views/staticPages/adminViews/adminSummary.html",                   //  2
  "views/staticPages/loginViews/loginRetry.html",                     //  3
  "views/staticPages/registrationViews/activateDescription.html",           //  4
  "views/staticPages/registrationViews/activated.html",                     //  5
  "views/staticPages/registrationViews/activationRetry.html",               //  6
  "views/staticPages/registrationViews/activationError.html",               //  7
  "views/staticPages/registrationViews/registerFailDuplicate.html",         //  8
//  "views/staticPages/documentation/descriptionsViews/descriptions.html",    // 9
//  "views/staticPages/documentation/guideViews/guides.html",                 // 10
);


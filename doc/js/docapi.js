var docVM;

$(document).ready(function() {
    $.ajax({
        type: 'GET',
        url: '/doc/api/documentation.php',
        //data: paramSet,
        dataType: 'json',
        error: function(xhr, textStatus, error) { getDataError(xhr, error, textStatus, this.url); },
        success: function(data) { getDataSuccess(data); }
    });
});

function getDataSuccess(data) {
    console.log(data);
    docVM = new docViewModel(data);
    ko.applyBindings(docVM);
}

function getDataError(xhr, error, textStatus, url) {

}

var docViewModel = function(data) {
    var _this = this;
    this.headerVMs = [];
    for (var i=0; i<data.headers.length;i++) {
        var headerVM = new headerViewModel(data.headers[i]);
        _this.headerVMs.push(headerVM);
    }

    return _this;
}

var headerViewModel = function(data) {
    var _this = this;
    this.headerID = ko.observable(data.headerID);
    this.headerText = ko.observable(data.headerText);
    this.accordionOpen = ko.observable(data.headerID === "1");
    this.sectionVMs = [];
    for (var i=0; i<data.sections.length; i++) {
        sectionVM = new sectionViewModel(_this, data.sections[i]);
        this.sectionVMs.push(sectionVM);
    }
    this.toggleClick = function() {
        for (var i=0; i<docVM.headerVMs.length; i++) {
            docVM.headerVMs[i].accordionOpen(false);
            for (var j=0; j<docVM.headerVMs[i].sectionVMs.length; j++) {
                docVM.headerVMs[i].sectionVMs[j].accordionOpen(false);
            }
        }
        _this.accordionOpen(true);
        for (var j=0; j<_this.sectionVMs.length; j++) {
            _this.sectionVMs[j].accordionOpen(_this.sectionVMs[j].sectionNo === 0);
        }
    }
    this.getHeaderClass = ko.computed(function() {
        return _this.accordionOpen() ? 'menu-item menu-item-has-children' : 'menu-item';
    });
    this.hasItems = ko.computed(function() {
        return _this.sectionVMs.length > 0;
    });
    return _this;
}

var sectionViewModel = function(parent, data) {
    var _this = this;
    this.parent = parent;
    this.sectionNo = data.sectionNo;
    this.accordionHeader = ko.observable(data.accordionHeader);
    this.accordionDescription = ko.observable(data.accordionDescription);
    this.content = ko.observable(data.content);
    this.accordionOpen = ko.observable(data.sectionNo === 0 && _this.parent.headerID() === "1");
    this.getSectionClass = ko.computed(function() {
        return _this.accordionOpen() ? "menu-item current-menu-item" : "menu-item";
    });
    this.getHeaderBreadcrumb = ko.computed(function() {
        return _this.parent.headerText();
    });
    this.toggleClick = function() {
        for (var i=0; i<_this.parent.sectionVMs.length; i++) {
            this.parent.sectionVMs[i].accordionOpen(false);
        }
        this.accordionOpen(true);
    }
    return _this;
}


"use strict";

var refereesInput = document.querySelector('#kt_tagify_referees');
var titlesInput = document.querySelector('#kt_tagify_titles');

let myObj = {
    refereesList: [],
    titlesList: [],
};

const refereesRequest = axios.get('/api/referees')
.then(refereesRequest => {
    myObj.refereesList = refereesRequest.data;
    console.log('resolved');
}).catch(refereesRequest => {
    console.log('error occurred');
});

const titlesRequest = axios.get('/api/titles')
.then(titlesRequest => {
    myObj.titlesList = titlesRequest.data;
    console.log('resolved');
}).catch(titlesRequest => {
    console.log('error occurred');
});


const refereesList2 = [
    { value: 1, name: 'Emma Smith' },
    { value: 2, name: 'Max Smith' },
    { value: 3, name: 'Sean Bean' },
    { value: 4, name: 'Brian Cox' },
    { value: 5, name: 'Francis Mitcham' },
    { value: 6, name: 'Dan Wilson' },
    { value: 7, name: 'Ana Crown' },
    { value: 8, name: 'John Miller' }
];

const titlesList2 = [
    { value: 1, name: 'Universal Title' },
    { value: 2, name: 'United States Title' },
    { value: 3, name: 'World Heavyweight Title' },
    { value: 4, name: 'Intercontinental Title' },
];

function tagTemplate(tagData) {
    return `
        <tag title="${(tagData.title)}"
                contenteditable='false'
                spellcheck='false'
                tabIndex="-1"
                class="${this.settings.classNames.tag} ${tagData.class ? tagData.class : ""}"
                ${this.getAttributes(tagData)}>
            <x title='' class='tagify__tag__removeBtn' role='button' aria-label='remove tag'></x>
            <div class="d-flex align-items-center">
                <span class='tagify__tag-text'>${tagData.name}</span>
            </div>
        </tag>
    `
}

function suggestionItemTemplate(tagData) {
    return `
        <div ${this.getAttributes(tagData)}
            class='tagify__dropdown__item d-flex align-items-center ${tagData.class ? tagData.class : ""}'
            tabindex="0"
            role="option">

            <div class="d-flex flex-column">
                <strong>${tagData.name}</strong>
            </div>
        </div>
    `
}

// initialize Tagify on the above input node reference
var refereesTagify = new Tagify(refereesInput, {
    tagTextProp: 'name', // very important since a custom template is used with this property as text. allows typing a "value" or a "name" to match input with whitelist
    enforceWhitelist: true,
    skipInvalid: true, // do not remporarily add invalid tags
    dropdown: {
        closeOnSelect: false,
        enabled: 0,
        classname: 'referees-list',
        searchKeys: ['name']  // very important to set by which keys to search for suggesttions when typing
    },
    templates: {
        tag: tagTemplate,
        dropdownItem: suggestionItemTemplate
    },
    whitelist: refereesList2
})

// initialize Tagify on the above input node reference
var titlesTagify = new Tagify(titlesInput, {
    tagTextProp: 'name', // very important since a custom template is used with this property as text. allows typing a "value" or a "name" to match input with whitelist
    enforceWhitelist: true,
    skipInvalid: true, // do not remporarily add invalid tags
    dropdown: {
        closeOnSelect: false,
        enabled: 0,
        classname: 'titles-list',
        searchKeys: ['name']  // very important to set by which keys to search for suggesttions when typing
    },
    templates: {
        tag: tagTemplate,
        dropdownItem: suggestionItemTemplate
    },
    whitelist: titlesList2
})

refereesTagify.on('dropdown:show dropdown:updated', onDropdownShow)
refereesTagify.on('dropdown:select', onSelectSuggestion)

titlesTagify.on('dropdown:show dropdown:updated', onDropdownShow)
titlesTagify.on('dropdown:select', onSelectSuggestion)

var addAllSuggestionsRefereeElm;
var addAllSuggestionsTitleElm;

function onDropdownShow(e) {
    var dropdownContentElm = e.detail.tagify.DOM.dropdown.content;

    if (refereesTagify.suggestedListItems.length > 1) {
        addAllSuggestionsRefereeElm = getAddAllSuggestionsRefereesElm();

        // insert "addAllSuggestionsElm" as the first element in the suggestions list
        dropdownContentElm.insertBefore(addAllSuggestionsRefereeElm, dropdownContentElm.firstChild)
    }

    if (titlesTagify.suggestedListItems.length > 1) {
        addAllSuggestionsTitleElm = getAddAllSuggestionsTitlesElm();

        // insert "addAllSuggestionsElm" as the first element in the suggestions list
        dropdownContentElm.insertBefore(addAllSuggestionsTitleElm, dropdownContentElm.firstChild)
    }
}

function onSelectSuggestion(e) {
    if (e.detail.elm == addAllSuggestionsElm)
        refereesTagify.dropdown.selectAll.call(refereesTagify);
        titlesTagify.dropdown.selectAll.call(titlesTagify);
}

// create a "add all" custom suggestion element every time the dropdown changes
function getAddAllSuggestionsRefereesElm() {
    // suggestions items should be based on "dropdownItem" template
    return refereesTagify.parseTemplate('dropdownItem', [{
        class: "addAll",
        name: "Add all",
    }])
}

// create a "add all" custom suggestion element every time the dropdown changes
function getAddAllSuggestionsTitlesElm() {
    // suggestions items should be based on "dropdownItem" template
    return titlesTagify.parseTemplate('dropdownItem', [{
        class: "addAll",
        name: "Add all",
    }])
}

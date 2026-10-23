<?php

// Messages for the tag team lifecycle exceptions. The tag-teams.errors keys hold the
// short sentences the roster screens show; these carry :context, for example "Tag team 'The Hart Foundation'".
return [
    'errors' => [
        'employ' => [
            'already_employed' => ':context is already employed.',
            'retired' => ':context is retired and cannot be employed. Unretire it first.',
            'future_employment' => ':context already has employment scheduled to start and cannot be employed again.',
            'partners_unavailable' => ':context cannot be employed because these partners are unavailable: :partners.',
            'no_current_partners' => 'No current partners available',
        ],
        'reinstate' => [
            'not_suspended' => ':context is not suspended and cannot be reinstated.',
        ],
        'release' => [
            'unemployed' => ':context is not employed and cannot be released.',
        ],
        'restore' => [
            'not_deleted' => ':context is not deleted and cannot be restored.',
            'name_conflict' => ':context cannot be restored because an active tag team is already named \':name\'. Resolve the name conflict first.',
        ],
        'retire' => [
            'unemployed' => ':context is not employed and cannot be retired.',
            'already_retired' => ':context is already retired.',
        ],
        'suspend' => [
            'unemployed' => ':context is not employed and cannot be suspended.',
            'already_suspended' => ':context is already suspended and cannot be suspended again.',
        ],
        'unretire' => [
            'not_retired' => ':context is not retired and cannot be unretired.',
            'name_conflict' => ':context cannot be unretired because an existing tag team is already named \':name\'.',
            'no_available_partners' => ':context cannot be unretired because none of its partners are available.',
            'insufficient_partners' => '{1} :context cannot be unretired because only :count partner is available and :required are required.|[0,*] :context cannot be unretired because only :count partners are available and :required are required.',
            'key_partners_unavailable' => ':context cannot be unretired because these key partners are unavailable: :partners.',
            'partner_deleted' => ':context cannot be unretired because :wrestler has been deleted.',
            'partner_on_another_tag_team' => ':context cannot be unretired because :wrestler is now on \':tag_team\'.',
        ],
    ],
];

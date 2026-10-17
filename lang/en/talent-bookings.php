<?php

declare(strict_types=1);

return [
    'title' => 'Talent bookings', 'back' => 'Back to event', 'intro' => 'Track each person’s agreement to attend. Confirmation does not reserve their schedule or make them eligible for a match.',
    'private' => 'Private to event editors. Fees are recorded in USD.', 'refresh' => 'Refresh', 'saved' => 'Booking saved.',
    'all' => 'All people', 'attention' => 'Needs attention', 'filter' => 'Show', 'clear' => 'Confirmed without warnings',
    'empty' => 'No talent on this card yet. Add a person to start tracking an agreement.', 'no_results' => 'No people match this filter.',
    'add' => 'Add a person', 'kind' => 'Role', 'wrestlers' => 'Wrestlers', 'referees' => 'Referees', 'choose' => 'Search talent', 'open' => 'Open booking',
    'edit' => 'Edit booking', 'history' => 'History', 'close' => 'Close', 'save' => 'Save booking', 'save_pending' => 'Save for reconfirmation',
    'attest' => 'Record agreement', 'attest_help' => 'Use Record agreement only when this person has agreed to the current event details and terms outside Ringside.',
    'status' => 'Status', 'fee' => 'Agreed fee (USD)', 'unknown_fee' => 'Leave blank if unknown; enter 0 only when agreed.',
    'arrival' => 'Arrival date and time', 'arrival_hint' => 'Enter the arrival in :timezone. It can be before the event day.',
    'travel' => 'Travel notes', 'reason' => 'Reason for cancellation or correction', 'reason_hint' => 'Required when cancelling or correcting a confirmed booking to Proposed or Contacted.',
    'reopen' => 'Reopening starts at Proposed. Previous agreements and fees stay in history.',
    'on_card' => 'On card', 'off_card' => 'Additional event booking', 'matches' => 'Matches: :numbers', 'history_empty' => 'No changes have been recorded.',
    'recorded_details' => 'Event details at this change',
    'agreed_details' => 'Last agreed event details', 'agreed_terms' => 'Last agreed terms', 'unknown' => 'Not recorded', 'unscheduled' => 'Unscheduled',
    'venue_unset' => 'Venue not set', 'card_warning' => 'The card has assignments that are no longer bookable. Review the event card.',
    'historical_warning' => 'Availability warnings describe the current roster, not availability on the past event date.',
    'saving' => 'Saving…',
    'states' => ['not_tracked' => 'Not tracked', 'proposed' => 'Proposed', 'contacted' => 'Contacted', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'],
    'warnings' => ['unavailable' => 'Confirmed — unavailable', 'reconfirmation' => 'Reconfirmation needed', 'removed' => 'Removed from card — review',
        'cancelled_card' => 'On card — cancelled', 'venue_unset' => 'Venue not set', 'venue_deleted' => 'Venue unavailable', 'not_tracked' => 'On card — not tracked'],
    'errors' => ['person' => 'Choose a wrestler or referee from this event’s promotion.',
        'reopen' => 'Reopen a cancelled booking as Proposed before recording a new agreement.',
        'reason' => 'Add a reason for this cancellation or correction.', 'confirm' => 'Schedule the event and explicitly record the person’s agreement before confirming.',
        'fee' => 'Enter a non-negative USD amount, up to 99999999.99, with at most two decimal places.'],
];

<?php

$MSG = array(

    /* Service / access */
    'BBS_OUT_OF_SERVICE'      => 'This bulletin board is currently out of service.',
    'ACCESS_PROHIBITED'       => 'Access is prohibited.',

    /* Generic / IO errors */
    'FAILED_TO_READ_MESSAGE'  => 'Failed to read message',
    'FAILED_TO_OPEN_LOG' => 'Failed to open log file: %s',
    'FAILED_TO_OUTPUT_LOG'    => 'Failed to output message log',
    'OLDLOG_TOO_LARGE'        => 'Message log file exceeds the file size limit',
    'PARTICIPANT_FILE_ERROR'  => 'Participant file output error',

    /* Posting / validation */
    'POSTING_SUSPENDED'       => 'The posting function of this bulletin board is currently suspended.',
    'ADMIN_ONLY_POSTING'      => 'Only administrators are allowed to post to the bulletin board.',
    'ADMIN_POST_DISABLED'     => 'Admin access via post form is disabled. Please use the Manage link.',
    'BAD_REFERER'             => 'Posts cannot be made from any URLs besides',
    'POST_TOO_WIDE'           => 'There are too many characters in the post contents.',
    'POST_TOO_MANY_LINES'     => 'There are too many line breaks in the post contents.',
    'POST_TOO_LARGE'          => 'The post contents are too large.',
    'NAME_TOO_LONG'           => 'There are too many characters in the name field. (Up to {MAXNAMELENGTH} characters)',
    'EMAIL_TOO_LONG'          => 'There are too many characters in the email field. (Up to {MAXMAILLENGTH} characters)',
    'TITLE_TOO_LONG'          => 'There are too many characters in the title field. (Up to {MAXTITLELENGTH} characters)',
    'POST_TOO_FAST'           => 'The time between posts is too short. Please try again.',
    'NGWORD_FOUND'            => 'The post contains prohibited words.',
    'SPAM_KUN'                => 'SPAM-KUN GTFO!!!',

    /* Caller / request */
    'INVALID_CALLER'          => 'Invalid caller.',
    'NO_PARAMETERS'           => 'There are no parameters.',
    'INVALID_PASSWORD'        => 'The password is incorrect.',

    /* Follow / search / reference */
    'MESSAGE_NOT_FOUND'       => 'The specified message could not be found.',
    'REFERENCE_NOT_FOUND'     => 'Reference post not found.',
    'REFERENCE_COLON'         => 'Reference:',
    'FOLLOW_UP_POST'          => 'Follow-up post',
    'POST_SEARCH'             => 'Post search',

    /* Completion / undo */
    'POST_COMPLETE'           => 'Post complete',
    'DELETION_COMPLETE'       => 'Deletion complete',
    'UNDO_POST_NOT_FOUND'     => 'The corresponding post was not found.',
    'UNDO_NOT_PERMITTED'      => 'The deletion of the corresponding post is not permitted.',

    /* Main list footer */
    'POSTS_RANGE_NEWEST_TO_OLDEST' => 'Shown above are posts {BINDEX} through {EINDEX}, in order of newest to oldest. ',
    'NO_UNREAD_MESSAGES'           => 'There are no unread messages. ',
    'NO_POSTS_BELOW'               => 'There are no posts below this point.',

    /* New post page */
    'NEW_POST' => 'New post',

    /* Labels / tags inserted into messages */
    'HACKER_TAG'       => ' (hacker)',
    'FRAUDSTER_TAG'    => ' (fraudster',
    'SELF_REPLY_TAG'   => ' (self-reply)',

    /* Weekdays */
    'SUNDAY'    => 'Sun',
    'MONDAY'    => 'Mon',
    'TUESDAY'   => 'Tue',
    'WEDNESDAY' => 'Wed',
    'THURSDAY'  => 'Thu',
    'FRIDAY'    => 'Fri',
    'SATURDAY'  => 'Sat',


    /* HTML titles */
    'TITLE_FOLLOWUP'         => 'Follow-up post (reply)',
    'TITLE_SEARCH_BY_USER'   => 'Search by user',
    'TITLE_THREAD_VIEW'      => 'Thread view',
    'TITLE_TREE_VIEW'        => 'Tree view',
    'TITLE_DELETE_POST'      => 'Delete post',

    /*  Additional UI strings  */
    'URL_REDIRECTION'        => 'URL redirection',
    'USER_SETTINGS'          => 'User settings',
    'COUNTER_ERROR'          => 'Counter error',

);

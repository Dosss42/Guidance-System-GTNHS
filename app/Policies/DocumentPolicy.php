<?php

namespace App\Policies;

/**
 * Shared counselor access; see CounselorRecordPolicy. Documents are never
 * deleted through the application until a retention policy is approved.
 */
class DocumentPolicy extends CounselorRecordPolicy {}

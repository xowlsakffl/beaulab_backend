<?php

namespace App\Domains\Faq\Queries\Staff;

use App\Domains\Faq\Models\Faq;

final class FaqDeleteForStaffQuery
{
    public function delete(Faq $faq): void
    {
        $faq->delete();
    }
}

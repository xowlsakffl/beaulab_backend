<?php

namespace App\Domains\VideoRequest\Policies;

use App\Domains\Partner\Models\AccountPartner;
use App\Domains\Staff\Models\AccountStaff;
use App\Domains\User\Models\AccountUser;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Policies\Partner\VideoRequestForPartnerPolicy;
use App\Domains\VideoRequest\Policies\Staff\VideoRequestForStaffPolicy;

final class VideoRequestPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, VideoRequest $videoRequest): bool
    {
        return $this->delegate($actor)->view($actor, $videoRequest);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, VideoRequest $videoRequest): bool
    {
        return $this->delegate($actor)->update($actor, $videoRequest);
    }

    public function delete(mixed $actor, VideoRequest $videoRequest): bool
    {
        return $this->delegate($actor)->delete($actor, $videoRequest);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(VideoRequestForStaffPolicy::class),
            $actor instanceof AccountPartner => app(VideoRequestForPartnerPolicy::class),
            $actor instanceof AccountUser => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, VideoRequest $videoRequest): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, VideoRequest $videoRequest): bool { return false; }
                public function delete(mixed $actor, VideoRequest $videoRequest): bool { return false; }
            },
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, VideoRequest $videoRequest): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, VideoRequest $videoRequest): bool { return false; }
                public function delete(mixed $actor, VideoRequest $videoRequest): bool { return false; }
            },
        };
    }
}

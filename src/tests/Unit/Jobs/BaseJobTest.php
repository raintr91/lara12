<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\BaseJob;

class BaseJobTest extends TestCase
{
    /**
     * Test job can be instantiated.
     */
    public function test_job_can_be_instantiated(): void
    {
        $job = new class extends BaseJob {
            public function handle()
            {
                return true;
            }
        };

        $this->assertInstanceOf(BaseJob::class, $job);
    }

    /**
     * Test job implements ShouldQueue interface.
     */
    public function test_job_implements_should_queue(): void
    {
        $job = new class extends BaseJob {
            public function handle()
            {
                return true;
            }
        };

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /**
     * Test job handle method can be called.
     */
    public function test_job_handle_can_be_called(): void
    {
        $job = new class extends BaseJob {
            public function handle()
            {
                return 'executed';
            }
        };

        $result = $job->handle();

        $this->assertEquals('executed', $result);
    }
}

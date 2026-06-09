<?php

namespace Tests\Feature\Attachment;

use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentClaimTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm = $this->createUser('project-manager');
        $this->project = Project::factory()->create(['owner_id' => $this->pm->id]);
    }

    /**
     * Full lifecycle: upload temporary → create parent → claim → verify file is moved.
     */
    public function test_full_temp_upload_and_claim_lifecycle(): void
    {
        // 1. Upload a temporary file
        Storage::disk('local')->makeDirectory('drafts');
        $file = UploadedFile::fake()->create('test-doc.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->pm)
            ->post('/api/v1/attachments/upload-temp', [
                'files' => [$file],
            ])
            ->assertCreated();

        $this->assertTrue($response->json('status'));
        $uploaded = $response->json('data');
        $this->assertCount(1, $uploaded);
        $uuid = $uploaded[0]['uuid'];

        // Verify the attachment is in DB as temp
        $attachment = Attachment::where('uuid', $uuid)->first();
        $this->assertNotNull($attachment);
        $this->assertEquals('temp', $attachment->status);
        $this->assertStringContainsString('drafts/', $attachment->disk_path);

        // Verify the file is physically in the drafts directory
        Storage::disk('local')->assertExists($attachment->disk_path);

        // 2. Create a task in the project
        $task = Task::factory()->create([
            'project_id'  => $this->project->id,
            'created_by'  => $this->pm->id,
            'assigned_to' => $this->pm->id,
        ]);

        // 3. Claim the temporary attachment into the task
        $claimResponse = $this->actingAs($this->pm)
            ->postJson('/api/v1/attachments/claim', [
                'parent_type' => 'tasks',
                'parent_id'   => $task->id,
                'uuids'       => [$uuid],
            ])
            ->assertOk();

        $this->assertTrue($claimResponse->json('status'));
        $claimed = $claimResponse->json('data');
        $this->assertCount(1, $claimed);

        // 4. Verify the attachment status changed to claimed
        $attachment->refresh();
        $this->assertEquals('claimed', $attachment->status);
        $this->assertStringContainsString('projects/', $attachment->disk_path);
        $this->assertEquals($task->id, $attachment->attachable_id);
        $this->assertEquals(Task::class, $attachment->attachable_type);

        // 5. Verify file was moved: old path gone, new path exists
        Storage::disk('local')->assertExists($attachment->disk_path);
        $this->assertFalse(Storage::disk('local')->exists('drafts/' . $uuid . '.pdf'));
    }

    /**
     * Cannot claim attachments that belong to another user.
     */
    public function test_cannot_claim_other_user_temp_attachments(): void
    {
        Storage::disk('local')->makeDirectory('drafts');
        $file = UploadedFile::fake()->create('report.xlsx', 50, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $uploadResponse = $this->actingAs($this->pm)
            ->post('/api/v1/attachments/upload-temp', [
                'files' => [$file],
            ])
            ->assertCreated();

        $uuid = $uploadResponse->json('data')[0]['uuid'];

        // Another user who is a project member tries to claim
        $otherUser = $this->createUser('developer');
        $this->project->members()->create([
            'user_id' => $otherUser->id,
            'role'    => 'developer',
        ]);

        $task = Task::factory()->create([
            'project_id'  => $this->project->id,
            'assigned_to' => $otherUser->id,
            'created_by'  => $this->pm->id,
        ]);

        // Other user should not be able to claim PM's temp attachment (not their upload)
        $claimResponse = $this->actingAs($otherUser)
            ->postJson('/api/v1/attachments/claim', [
                'parent_type' => 'tasks',
                'parent_id'   => $task->id,
                'uuids'       => [$uuid],
            ])
            ->assertOk();

        // No attachments were claimed (they don't own them)
        $this->assertCount(0, $claimResponse->json('data'));
    }

    /**
     * Unauthenticated users cannot upload or claim.
     */
    public function test_unauthenticated_cannot_upload_temp(): void
    {
        $file = UploadedFile::fake()->create('test.txt', 10, 'text/plain');

        $this->post('/api/v1/attachments/upload-temp', [
            'files' => [$file],
        ])->assertUnauthorized();
    }
}
<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\ImportUpload;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Wrong data must come off the list from inside the system — officers never
 * touch SQL. Two entry points, same promise: only the records the officer
 * confirmed go, the archived file itself always stays (so removal is fixed
 * by re-importing), and planting batches linked to a removed record survive
 * and become reportable again.
 */
class AdminHarvestRecordRemovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Mail::fake();
        Storage::fake('local');
    }

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => 'Test '.ucfirst($role),
            'email' => strtolower($role).'_'.uniqid().'@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    private function makeMember(string $name): Member
    {
        return Member::create(['name' => $name]);
    }

    private function makeReport(Member $member, string $date = '2025-12-01'): HarvestReport
    {
        return HarvestReport::create([
            'user_id' => null,
            'member_id' => $member->id,
            'crop_type' => 'Rice',
            'season' => 'wet',
            'land_area' => 1.0,
            'actual_yield_metric_tons' => 4.0,
            'actual_total_expenses' => 30000,
            'harvest_date' => $date,
        ]);
    }

    /** A row already in the archive, without going through an import. */
    private function archivedHarvest(string $name, int $imported = 1): ImportUpload
    {
        $path = 'imports/harvest/202610/'.$name;

        Storage::disk('local')->put($path, "Member Name,Crop,Season\n");

        return ImportUpload::create([
            'user_id' => null,
            'kind' => ImportUpload::KIND_HARVEST,
            'source' => ImportUpload::SOURCE_FILE,
            'original_name' => $name,
            'stored_path' => $path,
            'size_bytes' => 30,
            'rows_total' => $imported,
            'rows_imported' => $imported,
            'rows_skipped' => 0,
        ]);
    }

    private function csv(string $rows): UploadedFile
    {
        $header = 'Member Name,Crop,Season,Land Area (ha),Actual Yield (MT),Actual Expenses (PHP),Harvest Date';

        return UploadedFile::fake()->createWithContent('harvest-reports.csv', $header."\n".$rows);
    }

    public function test_ticked_records_are_removed_and_the_rest_stay(): void
    {
        $admin = $this->makeUser('admin');
        $member = $this->makeMember('Juan C. Reyes');
        $a = $this->makeReport($member, '2025-12-01');
        $b = $this->makeReport($member, '2025-12-02');
        $keep = $this->makeReport($member, '2025-12-03');

        $this->actingAs($admin)
            ->post(route('admin.harvests.reports.remove'), ['ids' => $a->id.','.$b->id])
            ->assertRedirect(route('admin.harvests.reports'))
            ->assertSessionHas('success', '2 records removed.');

        $this->assertDatabaseMissing('harvest_reports', ['id' => $a->id]);
        $this->assertDatabaseMissing('harvest_reports', ['id' => $b->id]);
        $this->assertDatabaseHas('harvest_reports', ['id' => $keep->id]);
        $this->assertDatabaseCount('harvest_reports', 1);
    }

    public function test_ticked_records_can_be_archived_and_restored(): void
    {
        $admin = $this->makeUser('admin');
        $member = $this->makeMember('Juan C. Reyes');
        $report = $this->makeReport($member);

        $this->actingAs($admin)
            ->post(route('admin.harvests.reports.archive'), ['ids' => (string) $report->id])
            ->assertRedirect(route('admin.harvests.reports'))
            ->assertSessionHas('success', '1 record archived.');

        $this->assertDatabaseHas('harvest_reports', [
            'id' => $report->id,
            'archived_at' => $report->fresh()->archived_at,
        ]);
        $this->assertNotNull($report->fresh()->archived_at);
        $this->assertSame(0, HarvestReport::count());

        $this->actingAs($admin)
            ->post(route('admin.archive.harvest-reports.restore', $report->id))
            ->assertRedirect(route('admin.archive', ['type' => 'harvest-reports']));

        $this->assertNull($report->fresh()->archived_at);
        $this->assertSame(1, HarvestReport::count());
    }

    public function test_removal_detaches_the_planting_batch_so_it_can_be_reported_again(): void
    {
        $admin = $this->makeUser('admin');
        $farmer = $this->makeUser('farmer');
        $member = $this->makeMember('Juan C. Reyes');
        $report = $this->makeReport($member);
        $rice = Crop::create(['name' => 'Rice', 'category' => 'Cereal', 'description' => 'Test rice']);

        $batch = FarmerCrop::create([
            'user_id' => $farmer->id,
            'crop_id' => $rice->id,
            'variety' => 'Inbred',
            'variety_key' => 'inbred',
            'land_area' => 1.0,
            'land_area_unit' => 'ha',
            'planting_date' => '2025-11-01',
            'season' => 'wet',
            'growth_stage' => 'harvested',
            'confirmation_status' => 'confirmed',
            'estimated_yield' => 4000,
            'yield_unit' => 'kg',
            'harvest_report_id' => $report->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.harvests.reports.remove'), ['ids' => (string) $report->id])
            ->assertSessionHas('success', '1 record removed.');

        // The record is gone; the planting batch survives, simply unlinked.
        $this->assertDatabaseMissing('harvest_reports', ['id' => $report->id]);
        $this->assertDatabaseHas('farmer_crops', ['id' => $batch->id]);
        $this->assertNull($batch->fresh()->harvest_report_id);
    }

    public function test_an_empty_selection_is_refused_instead_of_wiping_anything(): void
    {
        $admin = $this->makeUser('admin');
        $member = $this->makeMember('Juan C. Reyes');
        $this->makeReport($member);

        $this->actingAs($admin)
            ->post(route('admin.harvests.reports.remove'), ['ids' => ''])
            ->assertRedirect(route('admin.harvests.reports'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('harvest_reports', 1);
    }

    public function test_removing_a_wrong_file_takes_only_that_files_records_and_keeps_the_file(): void
    {
        $admin = $this->makeUser('admin');
        $member = $this->makeMember('Juan C. Reyes');
        $wrong = $this->archivedHarvest('wrong-file.csv', 2);
        $other = $this->archivedHarvest('good-file.csv', 1);

        $fromWrong1 = $this->makeReport($member, '2025-12-01');
        $fromWrong2 = $this->makeReport($member, '2025-12-02');
        $fromOther = $this->makeReport($member, '2025-12-03');
        $manual = $this->makeReport($member, '2025-12-04');

        $fromWrong1->update(['import_upload_id' => $wrong->id]);
        $fromWrong2->update(['import_upload_id' => $wrong->id]);
        $fromOther->update(['import_upload_id' => $other->id]);

        $this->actingAs($admin)
            ->post(route('admin.harvests.uploads.remove-records', $wrong))
            ->assertRedirect(route('admin.harvests.uploads'))
            ->assertSessionHas('success', '2 records removed from Harvest Reports. The file stays in Uploads.');

        // Only the wrong file's records went; the other file's and manual rows stay.
        $this->assertDatabaseMissing('harvest_reports', ['id' => $fromWrong1->id]);
        $this->assertDatabaseMissing('harvest_reports', ['id' => $fromWrong2->id]);
        $this->assertDatabaseHas('harvest_reports', ['id' => $fromOther->id]);
        $this->assertDatabaseHas('harvest_reports', ['id' => $manual->id]);

        // Neither the upload row nor the file itself is touched — a
        // re-import is how an accidental removal is undone.
        $this->assertDatabaseCount('import_uploads', 2);
        Storage::disk('local')->assertExists($wrong->stored_path);

        // With nothing left on the list, that file's row stops offering
        // removal and instead reports the state — the other file, still
        // linked to its records, keeps its own button.
        $this->actingAs($admin)
            ->get(route('admin.harvests.uploads'))
            ->assertOk()
            ->assertSee('0 of 2 still on the list')
            ->assertSee('1 of 1 imported');
    }

    public function test_members_files_never_offer_harvest_record_removal(): void
    {
        $admin = $this->makeUser('admin');
        $path = 'imports/members/202610/members.csv';
        Storage::disk('local')->put($path, "First Name,Last Name\nJuan,Dela Cruz\n");

        ImportUpload::create([
            'user_id' => null,
            'kind' => ImportUpload::KIND_MEMBERS,
            'source' => ImportUpload::SOURCE_FILE,
            'original_name' => 'members.csv',
            'stored_path' => $path,
            'size_bytes' => 30,
            'rows_total' => 1,
            'rows_imported' => 1,
            'rows_skipped' => 0,
        ]);

        // The members list shows no harvest-removal affordance…
        $this->actingAs($admin)
            ->get(route('admin.members.uploads'))
            ->assertOk()
            ->assertDontSee('Remove records');

        // …and posting the harvest route with a members file 404s.
        $this->actingAs($admin)
            ->post(route('admin.harvests.uploads.remove-records', ImportUpload::sole()))
            ->assertNotFound();
    }

    public function test_only_admins_can_remove_records(): void
    {
        $farmer = $this->makeUser('farmer');
        $member = $this->makeMember('Juan C. Reyes');
        $report = $this->makeReport($member);
        $upload = $this->archivedHarvest('wrong-file.csv');

        $this->actingAs($farmer)
            ->post(route('admin.harvests.reports.remove'), ['ids' => (string) $report->id])
            ->assertForbidden();

        $this->actingAs($farmer)
            ->post(route('admin.harvests.uploads.remove-records', $upload))
            ->assertForbidden();

        $this->assertDatabaseCount('harvest_reports', 1);
    }

    public function test_confirming_an_import_links_its_records_to_the_archived_file(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeMember('Juan C. Reyes');

        $this->actingAs($admin)
            ->post(route('admin.harvests.import.preview'), [
                'file' => $this->csv("Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15"),
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.harvests.import.confirm'), [
                'token' => session('admin.harvest_import.token'),
            ])
            ->assertRedirect(route('admin.harvests.reports'))
            ->assertSessionHas('success');

        $upload = ImportUpload::sole();
        $report = HarvestReport::sole();

        $this->assertSame(ImportUpload::KIND_HARVEST, $upload->kind);
        $this->assertSame($upload->id, $report->import_upload_id);

        // The upload page now offers removal for exactly these records…
        $this->actingAs($admin)
            ->get(route('admin.harvests.uploads'))
            ->assertOk()
            ->assertSee('Remove records');
    }

    public function test_reports_page_presents_selection_and_removal(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('admin.harvests.reports'))
            ->assertOk()
            ->assertSee('hrSelAll')
            ->assertSee('Remove selected');
    }
}

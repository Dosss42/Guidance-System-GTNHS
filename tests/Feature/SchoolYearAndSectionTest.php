<?php

namespace Tests\Feature;

use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SchoolYearAndSectionTest extends TestCase
{
    use RefreshDatabase;

    private User $counselor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->counselor = User::factory()->counselor()->create();
    }

    public function test_the_setup_page_lists_school_years_and_sections(): void
    {
        SchoolYear::factory()->count(2)->create();
        Section::factory()->count(3)->create();

        $this->actingAs($this->counselor)
            ->get(route('counselor.school-years.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('counselor/school-years/index')
                ->has('schoolYears', 2)
                ->has('sections', 3));
    }

    public function test_the_first_school_year_added_becomes_current(): void
    {
        $this->actingAs($this->counselor)
            ->post(route('counselor.school-years.store'), [
                'name' => '2026–2027',
                'start_date' => '2026-06-15',
                'end_date' => '2027-04-15',
            ])
            ->assertRedirect();

        $this->assertTrue(SchoolYear::sole()->is_current);
    }

    public function test_only_one_school_year_is_current_at_a_time(): void
    {
        $old = SchoolYear::factory()->startingIn(2025)->current()->create();
        $new = SchoolYear::factory()->startingIn(2026)->create();

        $this->actingAs($this->counselor)
            ->patch(route('counselor.school-years.current', $new))
            ->assertRedirect();

        $this->assertFalse($old->fresh()->is_current);
        $this->assertTrue($new->fresh()->is_current);
        $this->assertSame(1, SchoolYear::where('is_current', true)->count());
    }

    public function test_school_year_dates_and_names_are_validated(): void
    {
        SchoolYear::factory()->startingIn(2026)->create();

        $this->actingAs($this->counselor)
            ->post(route('counselor.school-years.store'), [
                'name' => '2026–2027',
                'start_date' => '2027-06-15',
                'end_date' => '2027-01-01',
            ])
            ->assertSessionHasErrors(['name', 'end_date']);
    }

    public function test_a_section_name_is_unique_within_its_grade_only(): void
    {
        Section::factory()->grade(11)->create(['name' => 'STEM-A']);

        $this->actingAs($this->counselor)
            ->post(route('counselor.sections.store'), ['grade_level' => 11, 'name' => ' STEM-A '])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->counselor)
            ->post(route('counselor.sections.store'), ['grade_level' => 12, 'name' => 'STEM-A'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->counselor)
            ->post(route('counselor.sections.store'), ['grade_level' => 13, 'name' => 'Extra'])
            ->assertSessionHasErrors('grade_level');

        $this->assertSame(2, Section::count());
    }

    public function test_a_section_can_be_renamed(): void
    {
        $section = Section::factory()->grade(10)->create(['name' => 'Sampagita']);

        $this->actingAs($this->counselor)
            ->patch(route('counselor.sections.update', $section), ['grade_level' => 10, 'name' => 'Sampaguita'])
            ->assertRedirect();

        $this->assertSame('Sampaguita', $section->fresh()->name);
        $this->assertSame($this->counselor->id, $section->fresh()->updated_by);
    }

    public function test_the_administrator_cannot_manage_school_years_or_sections(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('counselor.school-years.index'))->assertForbidden();
        $this->post(route('counselor.sections.store'), ['grade_level' => 7, 'name' => 'Rizal'])->assertForbidden();
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ExpertiseOption;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    private const AUTHOR_EXPERTISE_OPTIONS = [
        'SPMI',
        'AMI',
        'Tata Kelola Perguruan Tinggi',
        'OBE',
        'Akreditasi Nasional',
        'Akreditasi internasional',
        'Pemeringkatan PT Nasional dan Internasional (Qs Rank, THE Rank dll)',
        'Reformasi Birokrasi dan Zona Integritas',
        'Rencana Strategis dan Proses Bisnis PT',
        'SMAP ISO 37001',
        'SMOP ISO 21001',
        'SMM ISO 9001',
        'SML ISO 14001',
        'SMKI ISO 27001',
    ];

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return $this->renderRegistrationPage('user');
    }

    /**
     * Display the author registration view.
     */
    public function createAuthor(): Response
    {
        return $this->renderRegistrationPage('author');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        return $this->storeByRole($request, 'user');
    }

    /**
     * Handle an incoming author registration request.
     *
     * @throws ValidationException
     */
    public function storeAuthor(Request $request): RedirectResponse
    {
        return $this->storeByRole($request, 'author');
    }

    /**
     * Render a registration page variant.
     */
    private function renderRegistrationPage(string $registrationType): Response
    {
        $isAuthorRegistration = $registrationType === 'author';
        $expertiseOptions = $this->getAuthorExpertiseOptions();

        return Inertia::render('Auth/Register', [
            'registrationType' => $registrationType,
            'submitRoute' => $isAuthorRegistration ? 'register.author.store' : 'register.store',
            'title' => $isAuthorRegistration
                ? 'Daftar Sebagai Author'
                : 'Bergabung dengan expertia',
            'subtitle' => $isAuthorRegistration
                ? 'Untuk bisa menulis di expertia, Anda harus terdaftar sebagai peneliti atau akademisi pada sebuah universitas atau lembaga riset.'
                : 'Mulai sebagai user. Anda bisa berlangganan konten premium dan ajukan upgrade menjadi author dari akun Anda.',
            'switchLabel' => $isAuthorRegistration ? 'Daftar sebagai User' : 'Daftar langsung sebagai Author',
            'switchRoute' => $isAuthorRegistration ? 'register' : 'register.author',
            'expertiseOptions' => $expertiseOptions,
        ]);
    }

    /**
     * Store user and assign a public role.
     *
     * @throws ValidationException
     */
    private function storeByRole(Request $request, string $role): RedirectResponse
    {
        $isAuthor = $role === 'author';
        $expertiseOptions = $isAuthor ? $this->getAuthorExpertiseOptions() : [];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'position' => ['nullable', 'string', 'max:255'],
            'gender' => [$isAuthor ? 'required' : 'nullable', Rule::in(['Pria', 'Wanita'])],
            'whatsapp_number' => [$isAuthor ? 'required' : 'nullable', 'string', 'max:30'],
            'institution' => [$isAuthor ? 'required' : 'nullable', 'string', 'max:255'],
            'institution_unit' => [$isAuthor ? 'required' : 'nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'expertise_topics' => [$isAuthor ? 'required_without:expertise_other' : 'nullable', 'array'],
            'expertise_topics.*' => ['string', Rule::in($expertiseOptions)],
            'expertise_other' => [$isAuthor ? 'required_without:expertise_topics' : 'nullable', 'string', 'max:255'],
        ]);

        $expertiseAreas = $isAuthor ? $this->buildExpertiseAreas($validated) : null;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'position' => $validated['position'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'institution' => $validated['institution'] ?? null,
            'institution_unit' => $validated['institution_unit'] ?? null,
            'linkedin_url' => $validated['linkedin_url'] ?? null,
            'expertise_areas' => $expertiseAreas,
        ]);
        $user->syncRoles([$role]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, string>|null
     */
    private function buildExpertiseAreas(array $validated): ?array
    {
        $topics = collect($validated['expertise_topics'] ?? [])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values();

        $other = trim((string) ($validated['expertise_other'] ?? ''));

        if ($other !== '') {
            $topics->push("Other: {$other}");
        }

        return $topics->isEmpty() ? null : $topics->all();
    }

    /**
     * @return array<int, string>
     */
    private function getAuthorExpertiseOptions(): array
    {
        if (!Schema::hasTable('expertise_options')) {
            return self::AUTHOR_EXPERTISE_OPTIONS;
        }

        $options = ExpertiseOption::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && $name !== '')
            ->values()
            ->all();

        return count($options) > 0 ? $options : self::AUTHOR_EXPERTISE_OPTIONS;
    }
}

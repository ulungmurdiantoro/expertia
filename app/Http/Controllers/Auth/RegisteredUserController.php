<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ExpertiseAreas;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return $this->renderRegistrationPage('user');
    }

    /**
     * Form admin untuk membuat akun kontributor (author) setelah lolos kurasi.
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
        $user = $this->createUserWithRole($request, 'user');

        // Bila email gagal terkirim, pengguna tetap masuk dan bisa menekan "Kirim ulang" di halaman verifikasi.
        $this->dispatchRegistered($user);

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    /**
     * Admin membuat akun kontributor; admin tetap masuk dengan akunnya sendiri.
     *
     * @throws ValidationException
     */
    public function storeAuthor(Request $request): RedirectResponse
    {
        $role = $request->validate([
            'role' => ['required', Rule::in(['author', 'verified-expert'])],
        ])['role'];

        $user = $this->createUserWithRole($request, $role);

        if (! $this->dispatchRegistered($user)) {
            return redirect()
                ->route('admin.contributors.create')
                ->with('error', "Akun kontributor {$user->name} dibuat, tetapi email verifikasi gagal dikirim. Minta kontributor masuk lalu menekan \"Kirim ulang email verifikasi\", atau periksa pengaturan email server.");
        }

        $verificationNote = config('expertia.require_email_verification')
            ? " dan email verifikasi dikirim ke {$user->email}. Kontributor bisa menulis setelah memverifikasi email"
            : '';

        return redirect()
            ->route('admin.contributors.create')
            ->with('success', "Akun kontributor {$user->name} dibuat{$verificationNote}. Profil publiknya: /authors/{$user->profile_slug}");
    }

    /**
     * Event Registered mengirim email verifikasi. Akun sudah tersimpan, jadi kegagalan
     * kirim email dicatat ke log alih-alih menjadi halaman error.
     */
    private function dispatchRegistered(User $user): bool
    {
        try {
            event(new Registered($user));

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * Render a registration page variant.
     */
    private function renderRegistrationPage(string $registrationType): Response
    {
        $isAuthorRegistration = $registrationType === 'author';
        $expertiseOptions = ExpertiseAreas::options();

        return Inertia::render('Auth/Register', [
            'registrationType' => $registrationType,
            'adminMode' => $isAuthorRegistration,
            'submitRoute' => $isAuthorRegistration ? 'admin.contributors.store' : 'register.store',
            'title' => $isAuthorRegistration
                ? 'Tambah Akun Kontributor'
                : 'Bergabung dengan expertia',
            'subtitle' => $isAuthorRegistration
                ? 'Buat akun untuk kontributor yang sudah lolos kurasi. Nomor WhatsApp dan email hanya untuk koordinasi redaksi dan tidak tampil di profil publik.'
                : 'Daftar untuk menyimpan artikel, mengikuti penulis, dan ikut berdiskusi.',
            'switchLabel' => null,
            'switchRoute' => null,
            'expertiseOptions' => $expertiseOptions,
        ]);
    }

    /**
     * Buat user dan beri role. Role selain 'user' (author, verified-expert) wajib
     * melengkapi data profil kontributor.
     *
     * @throws ValidationException
     */
    private function createUserWithRole(Request $request, string $role): User
    {
        $isAuthor = $role !== 'user';
        $expertiseOptions = $isAuthor ? ExpertiseAreas::options() : [];

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

        $expertiseAreas = $isAuthor
            ? ExpertiseAreas::fromInput($validated['expertise_topics'] ?? [], $validated['expertise_other'] ?? null)
            : null;

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

        return $user;
    }
}

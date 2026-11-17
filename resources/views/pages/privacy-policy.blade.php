@extends('layouts.app')

@section('title', 'Kebijakan Privasi')
@section('meta_description', 'Kebijakan privasi website SMK Islamiyah Ciputat dan informasi penggunaan data formulir sekolah.')

@section('content')
    <section class="section public-page privacy-page">
        <div class="container public-page__container small-container">
            <header class="page-header">
                <span class="page-eyebrow">PRIVACY</span>
                <h1>Kebijakan Privasi</h1>
            </header>

            <div class="page-content editorial-content">
                <p>
                    Website {{ $siteSettings['identity.school_name'] }} berusaha menjaga keamanan dan kerahasiaan data yang diberikan oleh pengguna,
                    khususnya melalui formulir yang tersedia di website seperti formulir pendaftaran SPMB atau formulir kontak yang aktif.
                </p>

                <h2>Data yang dikumpulkan</h2>
                <p>
                    Data yang mungkin dikumpulkan dapat mencakup nama lengkap, nomor telepon, alamat email, serta informasi yang dibutuhkan
                    untuk keperluan pendaftaran, komunikasi, atau pengelolaan layanan sekolah. Jenis data yang dikumpulkan dapat berubah sesuai
                    kebutuhan formulir yang sedang aktif dan harus disesuaikan dengan kebutuhan operasional sekolah.
                </p>

                <h2>Tujuan penggunaan data</h2>
                <p>
                    Data yang dikumpulkan digunakan untuk keperluan administrasi sekolah, komunikasi resmi, proses pendaftaran, dan kepentingan
                    layanan pendidikan yang ditawarkan oleh sekolah. Data tidak digunakan untuk kepentingan di luar kebutuhan operasional yang sah.
                </p>

                <h2>Penanganan dan penyimpanan data</h2>
                <p>
                    Data akan disimpan dalam sistem yang dikelola sekolah dan hanya diakses oleh pihak yang berwenang. Sekolah berupaya menjaga
                    keamanan data menggunakan sistem yang sesuai dengan praktik keamanan dasar, namun pengguna tetap disarankan untuk menjaga kerahasiaan
                    informasi akun dan perangkat mereka sendiri.
                </p>

                <h2>Pihak yang dapat mengakses data</h2>
                <p>
                    Data hanya dapat diakses oleh pihak yang memiliki kewenangan resmi di lingkungan sekolah, termasuk tim administrasi atau petugas
                    yang menangani formulir dan komunikasi sekolah. Informasi tidak dibagikan secara umum kecuali jika ada kebutuhan resmi yang diizinkan
                    oleh peraturan yang berlaku.
                </p>

                <h2>Kontak terkait data</h2>
                <p>
                    Jika pengguna ingin menanyakan, memperbarui, atau mengklarifikasi data yang telah diberikan, dapat menghubungi sekolah melalui
                    email atau nomor kontak resmi yang tersedia di website. Informasi kontak yang dipublikasikan harus selalu sesuai dengan nomor atau
                    alamat email resmi sekolah yang telah dikonfirmasi.
                </p>

                <h2>Perubahan kebijakan</h2>
                <p>
                    Kebijakan privasi ini dapat diperbarui sesuai kebutuhan operasional sekolah atau perubahan formulir yang digunakan di website.
                    Perubahan akan disesuaikan dengan kebutuhan yang benar-benar berlaku di sekolah.
                </p>

                <p>
                    Catatan: halaman ini dibuat berdasarkan informasi yang saat ini tersedia di website dan kebutuhan umum pengelolaan formulir sekolah.
                    Detail operasional pengumpulan data tertentu dapat diperbarui oleh admin sekolah jika ada kebutuhan khusus yang belum tercantum.
                </p>
            </div>
        </div>
    </section>
@endsection

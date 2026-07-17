<!-- Developed By Febri Sutomo -->
<!-- https://github.com/febrisutomo -->
<!-- febrisutomo@gmail.com -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Pertashop App</title>

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.3.0/css/OverlayScrollbars.min.css"
        integrity="sha512-ZVVoM7L0mJANR/tsZmE6JqvKq8VG8Ry7YVFc6WioMUNkQiU0tzuuJLTKmKuz7vaDIqiYaeDTvlcrURXmOdnqlg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom-theme.css') }}">
    @stack('style')

</head>

<body
    class="hold-transition {{ Auth::user()->role == 'operator' ? 'layout-top-nav' : 'sidebar-mini layout-fixed layout-navbar-fixed' }}">

    @if (Auth::user()->role == 'operator')
        @include('layouts._operator')
    @else
        @include('layouts._admin')
    @endif

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"
        integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.1/js/bootstrap.bundle.min.js"
        integrity="sha512-mULnawDVcCnsk9a4aG1QLZZ6rcce/jSzEGqUkeOLy0b6q0+T6syHrxlsAGH7ZVoqC93Pd0lBqd6WguPWih7VHA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.3.0/js/OverlayScrollbars.min.js"
        integrity="sha512-TuTpz+pcpt4iP0NRpsgIXjew7KvI0u63QbaoAhcpqwlfHkPCDxp/Rar4K9Cd+mvyejGJwwUFtDqX+L1foZPJyQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.0/dist/chart.min.js"></script>

    <script src="{{ asset('js/adminlte.min.js') }}"></script>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })


        function formatDate(inputDate) {
            const date = new Date(inputDate);
            const options = {
                year: 'numeric',
                month: 'long',
                day: '2-digit',
                weekday: 'long',
            };
            return date.toLocaleDateString('id-ID', options);
        }

        function formatNumber(data, fractionDigit = 2) {
            return parseFloat(data).toLocaleString('id-ID', {
                minimumFractionDigits: fractionDigit,
                maximumFractionDigits: fractionDigit
            });
        }

        function formatCurrency(data, fractionDigit = 2) {
            return parseFloat(data).toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: fractionDigit,
                maximumFractionDigits: fractionDigit
            });
        }

        $(document).ready(function() {
            $('.currency').each(function() {
                var value = parseFloat($(this).text());
                if (!isNaN(value)) {
                    var formattedValue = formatCurrency(value, 0);
                    $(this).text(formattedValue);
                }
            });

            $('.number').each(function() {
                var value = parseFloat($(this).text());
                if (!isNaN(value)) {
                    var formattedValue = formatNumber(value);
                    $(this).text(formattedValue);
                }
            });

            $('.number-int').each(function() {
                var value = parseFloat($(this).text());
                if (!isNaN(value)) {
                    var formattedValue = formatNumber(value, 0);
                    $(this).text(formattedValue);
                }
            });
            $('.number-abs').each(function() {
                var value = Math.abs(parseFloat($(this).text()));
                if (!isNaN(value)) {
                    var formattedValue = formatNumber(value);
                    $(this).text(formattedValue);
                }
            });
        });


        function logout(e) {
            e.preventDefault()
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Anda yakin ingin logout?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#logout-form').submit()
                }
            });
        }


        @if (session('success'))
            var popupId = "{{ uniqid() }}";
            if (!sessionStorage.getItem('shown-' + popupId)) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '{{ session('success') }}',
                    showConfirmButton: false,
                    timer: 1500 // milliseconds
                });
            }
            sessionStorage.setItem('shown-' + popupId, '1');
        @endif
    </script>

    @stack('script')

</body>

</html>

@extends('adminlte::page')

@section('title', 'Agregar Scrap Default')

@section('content_header')
    <h1>{{ __('Agregar Scrap Default') }}</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body">
            <form action="{{ route('part-number-default-scraps.store', $partNumber) }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <!-- Número de Parte -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary">{{ __('Número de Parte') }}</label>
                        <input type="text"
                               class="form-control border-light-subtle"
                               value="{{ $partNumber->number }} — {{ $partNumber->name }}"
                               disabled>
                    </div>

                    <!-- Tipo de Scrap -->
                    <div class="col-md-6">
                        <label for="scrap_code" class="form-label fw-bold text-secondary">
                            {{ __('Tipo de Scrap') }} *
                        </label>
                        <select class="form-control select2 border-light-subtle @error('scrap_code') is-invalid @enderror"
                                id="scrap_code" name="scrap_code" required>
                            <option value="">Seleccione un tipo de scrap...</option>
                            @foreach ($scraps as $scrap)
                                <option value="{{ $scrap->code }}"
                                    {{ old('scrap_code') == $scrap->code ? 'selected' : '' }}>
                                    {{ $scrap->code }} — {{ $scrap->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('scrap_code')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Cantidad -->
                    <div class="col-md-6">
                        <label for="quantity" class="form-label fw-bold text-secondary">
                            {{ __('Cantidad') }} *
                        </label>
                        <input type="number" min="0.01" step="0.01"
                               class="form-control border-light-subtle @error('quantity') is-invalid @enderror"
                               id="quantity" name="quantity"
                               value="{{ old('quantity', 1) }}"
                               placeholder="Ingrese la cantidad de scrap"
                               required>
                        @error('quantity')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <!-- Descripción -->
                    <div class="col-md-6">
                        <label for="description" class="form-label fw-bold text-secondary">
                            {{ __('Descripción') }}
                        </label>
                        <input type="text"
                               class="form-control border-light-subtle @error('description') is-invalid @enderror"
                               id="description" name="description"
                               value="{{ old('description') }}"
                               placeholder="Ej: Primera pieza, Pieza media, Última pieza">
                        <small class="text-muted">Opcional — describe qué pieza representa este scrap</small>
                        @error('description')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('part-numbers.edit', $partNumber) }}"
                       class="btn btn-outline-secondary rounded-3">
                        <i class="fas fa-times mr-2"></i>
                        {{ __('Cancelar') }}
                    </a>
                    <button type="submit" class="btn btn-primary rounded-3">
                        <i class="fas fa-save mr-2"></i>
                        {{ __('Guardar Scrap Default') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        body,
        .main-header,
        .main-sidebar,
        .content-wrapper,
        .card,
        .btn,
        .form-control,
        .form-select,
        .form-label,
        .table,
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Roboto', sans-serif !important;
        }

        .border-light-subtle {
            border-color: #f0f0f0 !important;
        }

        .form-control {
            border-radius: 8px !important;
            padding: 0.5rem 1rem !important;
            font-size: 0.95rem !important;
            border: 1px solid #e0e0e0 !important;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
            outline: none !important;
        }

        .form-control:disabled {
            background-color: #f8f9fa !important;
            color: #495057 !important;
            cursor: not-allowed;
        }

        .form-label {
            font-size: 0.9rem !important;
            margin-bottom: 0.5rem !important;
            font-weight: 600 !important;
        }

        /* Select2 */
        .select2-container--default .select2-selection--single {
            height: 38px !important;
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px !important;
            padding: 0 !important;
            font-family: 'Roboto', sans-serif !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 12px !important;
            padding-right: 20px !important;
            font-size: 0.95rem !important;
            color: #495057 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #6c757d !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }

        .select2-dropdown {
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
        }

        .select2-results__option {
            font-family: 'Roboto', sans-serif !important;
            font-size: 0.95rem !important;
            padding: 8px 12px !important;
        }

        .select2-results__option--highlighted {
            background-color: #007bff !important;
        }

        /* Botones */
        .btn {
            border-radius: 8px !important;
            padding: 0.5rem 1.5rem !important;
            font-weight: 500 !important;
            font-size: 0.95rem !important;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .btn i {
            font-size: 0.9rem !important;
            margin-right: 0.5rem !important;
        }

        .gap-2 { gap: 0.5rem; }
        .rounded-3 { border-radius: 12px !important; }
        .invalid-feedback { font-size: 0.85rem !important; }

        ::placeholder {
            color: #6c757d !important;
            opacity: 0.7;
        }

        .alert { border-radius: 8px; }
        .btn-close { background-size: 0.75rem; padding: 0.5rem; }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            // Inicializar Select2
            $('#scrap_code').select2({
                placeholder: 'Seleccione un tipo de scrap',
                allowClear: false,
                width: '100%'
            });

            // Foco automático
            setTimeout(function () {
                $('#scrap_code').select2('open');
                $('#scrap_code').select2('close');
            }, 100);

            // Cerrar alertas automáticamente después de 5 segundos
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                });
            }, 5000);
        });
    </script>
@stop

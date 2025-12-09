@extends('adminlte::page')

@section('title', 'Scrap')

@section('content_header')
    <h1>{{ __('Scrap') }}</h1>
@stop

@section('content')
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body">
            <form action="{{ route('scrap-records.store') }}" method="POST" id="scrapForm">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="part_number_id" class="form-label fw-bold text-secondary">{{ __('Número de Parte') }}
                            *</label>
                        <select
                            class="form-control select2 border-light-subtle @error('part_number_id') is-invalid @enderror"
                            id="part_number_id" name="part_number_id" required>
                            <option value="">Seleccione un número de parte...</option>
                            @foreach ($partNumbers as $partNumber)
                                <option value="{{ $partNumber->id }}"
                                    {{ old('part_number_id') == $partNumber->id ? 'selected' : '' }}>
                                    {{ $partNumber->number }} - {{ $partNumber->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('part_number_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="scrap_id" class="form-label fw-bold text-secondary">{{ __('Tipo de Scrap') }} *</label>
                        <select class="form-control select2 border-light-subtle @error('scrap_id') is-invalid @enderror"
                            id="scrap_id" name="scrap_id" required>
                            <option value="">Seleccione un tipo de scrap...</option>
                            @foreach ($scraps as $scrap)
                                <option value="{{ $scrap->id }}" {{ old('scrap_id') == $scrap->id ? 'selected' : '' }}>
                                    {{ $scrap->code }} - {{ $scrap->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('scrap_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="quantity" class="form-label fw-bold text-secondary">{{ __('Cantidad') }} *</label>
                        <input type="number" min="0" step="0.01"
                            class="form-control border-light-subtle @error('quantity') is-invalid @enderror" id="quantity"
                            name="quantity" value="{{ old('quantity') }}" placeholder="Ingrese la cantidad de scrap"
                            required>
                        @error('quantity')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <small class="text-muted">
                            Ingrese la cantidad de scrap generado. Use decimales si es necesario.
                        </small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ $source == 'production-records.index' ? route('production-records.index') : route('scrap-records.index') }}"
                        class="btn btn-outline-secondary rounded-3">
                        <i class="fas fa-times"></i>
                        <span class="ms-2">{{ __('Cancelar') }}</span>
                    </a>
                    <button type="submit" class="btn btn-primary rounded-3">
                        <i class="fas fa-save"></i>
                        <span class="ms-2">{{ __('Guardar Registro') }}</span>
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
        .card,
        .btn,
        .form-control,
        .table,
        h1 {
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

        .form-label {
            font-size: 0.9rem !important;
            margin-bottom: 0.5rem !important;
            font-weight: 600 !important;
        }

        .select2-container--default .select2-selection--single {
            height: 38px !important;
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px !important;
            padding: 0 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 12px !important;
            font-size: 0.95rem !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }

        .btn {
            border-radius: 8px !important;
            padding: 0.5rem 1.5rem !important;
            font-weight: 500 !important;
            font-size: 0.95rem !important;
            transition: all 0.2s ease;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .rounded-3 {
            border-radius: 12px !important;
        }

        .invalid-feedback {
            font-size: 0.85rem !important;
        }

        ::placeholder {
            color: #6c757d !important;
            opacity: 0.7;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('#part_number_id, #scrap_id').select2({
                placeholder: 'Seleccione una opción',
                allowClear: false,
                width: '100%'
            });

            // Foco automático
            setTimeout(function() {
                $('#part_number_id').select2('focus');
            }, 100);

            // Validación de cantidad
            $('#quantity').on('input', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
            });

            // Confirmación al enviar
            $('#scrapForm').on('submit', function(e) {
                const quantity = $('#quantity').val();
                if (quantity && quantity > 100) {
                    e.preventDefault();
                    Swal.fire({
                        title: '¿Confirmar cantidad?',
                        text: `La cantidad ingresada es ${quantity}. ¿Es correcto?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, es correcto',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.submit();
                        }
                    });
                }
            });
        });
    </script>
@stop

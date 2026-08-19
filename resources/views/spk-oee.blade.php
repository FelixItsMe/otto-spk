@extends('layouts.spk')

@section('content')
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3">Import File OEE Bulanan</h5>
        <form action="{{ route('spk-oee.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
            @csrf

            <div class="col-md-4">
                <label class="form-label">File Data (.xlsx)</label>
                <input type="file" name="data_file" class="form-control" accept=".xlsx" required>
                <small class="text-muted">Format: header Proses, POT, ... sampai Good Output.</small>
                @error('data_file')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-orange px-4">
                    <i class="fa-solid fa-file-import me-2"></i> Import Data OEE
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

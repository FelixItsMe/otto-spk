@extends('layouts.spk')

@section('content')
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3">Import File OEE Bulanan</h5>
        <form action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
            @csrf
            <input type="hidden" name="mode" value="import">

            <div class="col-md-4">
                <label class="form-label">Pilih Bulan</label>
                <select name="upload_month" class="form-select" required>
                    <option value="">Pilih Bulan</option>
                    @foreach ($uploadMonths as $key => $month)
                        <option value="{{ $key + 1 }}" {{ (string) old('upload_month') === (string) ($key + 1) ? 'selected' : '' }}>{{ $month }}</option>
                    @endforeach
                </select>
                @error('upload_month')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Pilih Tahun Data</label>
                <select name="upload_year" class="form-select" required>
                    <option value="">Pilih Tahun</option>
                    @foreach ($uploadYears as $year)
                        <option value="{{ $year }}" {{ (string) old('upload_year') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
                @error('upload_year')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">File Data (.xlsx)</label>
                <input type="file" name="data_file" class="form-control" accept=".xlsx" required>
                <a href="{{ route('upload.template') }}" target="_blank" class="text-muted">Download Template</a>
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

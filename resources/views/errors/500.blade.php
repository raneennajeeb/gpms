@extends('layouts.error', ['code' => 500, 'icon' => 'alert-circle'])

@section('title', __('errors.500_title'))
@section('message', __('errors.500_text'))

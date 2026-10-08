@extends('layouts.error', ['code' => 404, 'icon' => 'search'])

@section('title', __('errors.404_title'))
@section('message', __('errors.404_text'))

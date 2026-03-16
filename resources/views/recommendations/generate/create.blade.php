@extends('layouts.master')

@section('title') Create New Recommendation @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    /* Wizard Styles */
    .wizard-progress-container {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .wizard-progress-bar {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }

    .wizard-progress-bar .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #556ee6, #34c38f);
        transition: width 0.5s ease-in-out;
    }

    .wizard-steps-indicator {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding: 0 2px;
    }

    .wizard-steps-indicator .step-label {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #e0e0e0;
        transition: all 0.3s ease;
        cursor: pointer;
        display: inline-block;
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        outline: none;
    }

    .wizard-steps-indicator .step-label:hover {
        background-color: #c0c0c0;
        transform: scale(1.3);
    }

    .wizard-steps-indicator .step-label.active {
        background-color: #556ee6;
        transform: scale(1.4);
        box-shadow: 0 0 0 3px rgba(85, 110, 230, 0.25);
    }

    .wizard-steps-indicator .step-label.completed {
        background-color: #34c38f;
    }

    .wizard-steps-indicator .step-label.completed:hover {
        background-color: #2ca67a;
        transform: scale(1.3);
    }

    .wizard-step {
        min-height: 400px;
        padding: 20px 0;
        opacity: 1;
    }

    .wizard-step.d-none {
        display: none !important;
        opacity: 0;
    }

    .wizard-step.slide-out-left {
        animation: slideOutToLeft 0.4s ease-in-out forwards;
    }

    .wizard-step.slide-out-right {
        animation: slideOutToRight 0.4s ease-in-out forwards;
    }

    .wizard-step.slide-in-left {
        animation: slideInFromLeft 0.4s ease-in-out forwards;
    }

    .wizard-step.slide-in-right {
        animation: slideInFromRight 0.4s ease-in-out forwards;
    }

    @keyframes slideOutToLeft {
        from {
            opacity: 1;
            transform: none;
        }
        to {
            opacity: 0;
            transform: translateX(-50px);
        }
    }

    @keyframes slideOutToRight {
        from {
            opacity: 1;
            transform: none;
        }
        to {
            opacity: 0;
            transform: translateX(50px);
        }
    }

    @keyframes slideInFromRight {
        from {
            opacity: 0;
            transform: translateX(50px);
        }
        to {
            opacity: 1;
            transform: none;
        }
    }

    @keyframes slideInFromLeft {
        from {
            opacity: 0;
            transform: translateX(-50px);
        }
        to {
            opacity: 1;
            transform: none;
        }
    }

    .wizard-step-content {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 40px;
        min-height: 350px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 2px dashed #dee2e6;
    }

    .wizard-step-content i {
        font-size: 4rem;
        color: #adb5bd;
        margin-bottom: 1rem;
        transition: transform 0.3s ease;
    }

    .wizard-step:not(.d-none) .wizard-step-content i {
        animation: iconPulse 0.6s ease-out;
    }

    @keyframes iconPulse {
        0% {
            transform: scale(0.8);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .wizard-step-content h5 {
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .wizard-step-content p {
        color: #6c757d;
        margin-bottom: 0;
    }

    .wizard-navigation {
        display: flex;
        justify-content: space-between;
        padding-top: 20px;
        border-top: 1px solid #e9ecef;
        margin-top: 20px;
    }

    .wizard-navigation .btn {
        transition: all 0.3s ease;
    }

    .wizard-navigation .btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .wizard-navigation .btn:active:not(:disabled) {
        transform: translateY(0);
    }

    /* Prevent text cursor on all wizard content */
    .wizard-step {
        user-select: none;
        caret-color: transparent;
    }

    .wizard-step * {
        caret-color: transparent;
    }

    /* Prevent cursor in variety finder modal */
    #varietyFinderModal .modal-body {
        user-select: none;
        caret-color: transparent;
    }

    #varietyFinderModal .modal-body * {
        caret-color: transparent;
    }

    /* ONLY allow cursor in variety section inputs */
    #variety-section input,
    #variety-section textarea,
    #manual-entry-section input,
    #manual-entry-section textarea {
        caret-color: #495057 !important;
        user-select: text !important;
        cursor: text !important;
    }

    #variety-section input:focus,
    #variety-section textarea:focus,
    #manual-entry-section input:focus,
    #manual-entry-section textarea:focus {
        caret-color: #556ee6 !important;
    }

    /* Allow cursor in Step 4 and Step 5 inputs */
    #step-4 input,
    #step-5 input,
    #step-5 select {
        caret-color: #495057 !important;
        user-select: text !important;
        cursor: text !important;
    }

    #step-4 input:focus,
    #step-5 input:focus {
        caret-color: #556ee6 !important;
    }

    /* Step 1: Crop Selection Styles */
    .step-1-content {
        padding: 20px;
    }

    .step-1-content .row {
        max-width: 650px;
        margin: 0 auto;
    }

    .crop-selection-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 16px;
        padding: 30px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        width: 100%;
        max-width: 280px;
        min-height: 280px;
        margin: 0 auto;
        aspect-ratio: 1 / 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .crop-selection-box:hover {
        border-color: #556ee6;
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(85, 110, 230, 0.15);
    }

    .crop-selection-box.selected {
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.05) 0%, rgba(52, 195, 143, 0.1) 100%);
        box-shadow: 0 10px 30px rgba(52, 195, 143, 0.2);
    }

    .crop-selection-box.selected .crop-title {
        color: #34c38f;
    }

    .crop-icon {
        width: 120px;
        height: 120px;
        margin: 0 auto 20px;
        transition: all 0.3s ease;
    }

    .crop-selection-box:hover .crop-icon {
        transform: scale(1.1);
    }

    .crop-selection-box.selected .crop-icon {
        transform: scale(1.1);
    }

    .crop-icon svg {
        width: 100%;
        height: 100%;
    }

    .crop-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 12px;
    }

    .crop-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
        transition: color 0.3s ease;
    }

    .crop-subtitle {
        font-size: 0.95rem;
        color: #74788d;
        margin-bottom: 0;
    }

    .crop-check {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 30px;
        height: 30px;
        background: #34c38f;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.2rem;
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
    }

    .crop-selection-box.selected .crop-check {
        opacity: 1;
        transform: scale(1);
    }

    /* Animation for selection */
    @keyframes selectPulse {
        0% {
            box-shadow: 0 0 0 0 rgba(52, 195, 143, 0.4);
        }
        70% {
            box-shadow: 0 0 0 15px rgba(52, 195, 143, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(52, 195, 143, 0);
        }
    }

    .crop-selection-box.selected {
        animation: selectPulse 0.6s ease-out;
    }

    /* Shake animation for validation error */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .crop-selection-box.shake-animation {
        animation: shake 0.5s ease-in-out;
        border-color: #f46a6a !important;
    }

    /* Step 2: Breed Type Selection Styles */
    .step-2-content {
        padding: 20px;
    }

    .step-2-content .row {
        max-width: 650px;
        margin: 0 auto;
    }

    .breed-selection-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 16px;
        padding: 30px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        width: 100%;
        max-width: 280px;
        min-height: 280px;
        margin: 0 auto;
        aspect-ratio: 1 / 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .breed-selection-box:hover {
        border-color: #556ee6;
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(85, 110, 230, 0.15);
    }

    .breed-selection-box.selected {
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.05) 0%, rgba(52, 195, 143, 0.1) 100%);
        box-shadow: 0 10px 30px rgba(52, 195, 143, 0.2);
        animation: selectPulse 0.6s ease-out;
    }

    .breed-selection-box.selected .breed-title {
        color: #34c38f;
    }

    .breed-icon {
        width: 120px;
        height: 120px;
        margin: 0 auto 20px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .breed-selection-box:hover .breed-icon {
        transform: scale(1.1);
    }

    .breed-selection-box.selected .breed-icon {
        transform: scale(1.1);
    }

    .breed-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 12px;
    }

    .breed-icon i {
        font-size: 4rem;
    }

    .breed-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
        transition: color 0.3s ease;
    }

    .breed-subtitle {
        font-size: 0.85rem;
        color: #74788d;
        margin-bottom: 0;
        font-style: italic;
    }

    .breed-check {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 26px;
        height: 26px;
        background: #34c38f;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1rem;
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
    }

    .breed-selection-box.selected .breed-check {
        opacity: 1;
        transform: scale(1);
    }

    .breed-selection-box.shake-animation {
        animation: shake 0.5s ease-in-out;
        border-color: #f46a6a !important;
    }

    /* Step 3: Planting/Cropping System Styles */
    .step-3-content {
        padding: 20px;
    }

    .planting-system-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .planting-system-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 16px;
        padding: 25px 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        height: 100%;
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .planting-system-box:hover {
        border-color: #556ee6;
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(85, 110, 230, 0.15);
    }

    .planting-system-box.selected {
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.05) 0%, rgba(52, 195, 143, 0.1) 100%);
        box-shadow: 0 10px 30px rgba(52, 195, 143, 0.2);
        animation: selectPulse 0.6s ease-out;
    }

    .planting-system-box.selected .planting-system-title {
        color: #34c38f;
    }

    .planting-system-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 15px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .planting-system-box:hover .planting-system-icon {
        transform: scale(1.1);
    }

    .planting-system-box.selected .planting-system-icon {
        transform: scale(1.1);
    }

    .planting-system-icon svg {
        width: 100%;
        height: 100%;
    }

    .planting-system-icon:has(img) {
        width: 50%;
        height: auto;
    }

    .planting-system-icon img {
        width: 100%;
        height: auto;
        border-radius: 10px;
    }

    .planting-system-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
        transition: color 0.3s ease;
    }

    .planting-system-subtitle {
        font-size: 0.85rem;
        color: #74788d;
        font-style: italic;
        margin-bottom: 5px;
    }

    .planting-system-desc {
        font-size: 0.8rem;
        color: #adb5bd;
        margin-bottom: 0;
        line-height: 1.4;
    }

    .planting-system-check {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 26px;
        height: 26px;
        background: #34c38f;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1rem;
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
    }

    .planting-system-box.selected .planting-system-check {
        opacity: 1;
        transform: scale(1);
    }

    .planting-system-box.shake-animation {
        animation: shake 0.5s ease-in-out;
        border-color: #f46a6a !important;
    }

    .planting-system-info-btn {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 24px;
        height: 24px;
        background: #556ee6;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        z-index: 5;
    }

    .planting-system-info-btn:hover {
        background: #3b5bdb;
        transform: scale(1.1);
    }

    /* Step 4: Farm Size Styles */
    .step-4-content {
        padding: 20px;
    }

    .farm-size-container {
        max-width: 600px;
        margin: 0 auto;
    }

    .farm-size-input-group {
        max-width: 400px;
        margin: 0 auto;
    }

    .farm-preset-btn {
        min-width: 70px;
        font-weight: 500;
    }

    .farm-preset-btn.active {
        background-color: #556ee6;
        border-color: #556ee6;
        color: #fff;
    }

    /* Step 5: Farm Location Styles */
    .step-5-content {
        padding: 20px;
    }

    .farm-location-container {
        max-width: 700px;
        margin: 0 auto;
    }

    .location-field-wrapper {
        position: relative;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        transition: all 0.3s ease;
        background: #fff;
    }

    .location-field-wrapper.active-field {
        border-color: #556ee6;
        background: linear-gradient(135deg, rgba(85, 110, 230, 0.03) 0%, rgba(85, 110, 230, 0.08) 100%);
        box-shadow: 0 4px 15px rgba(85, 110, 230, 0.15);
    }

    .location-field-wrapper.completed-field {
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.03) 0%, rgba(52, 195, 143, 0.08) 100%);
    }

    .location-field-wrapper.disabled-field {
        opacity: 0.6;
        background: #f8f9fa;
    }

    .location-field-wrapper .step-indicator {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #e9ecef;
        color: #74788d;
        font-size: 0.75rem;
        font-weight: 600;
        margin-right: 8px;
        transition: all 0.3s ease;
    }

    .location-field-wrapper.active-field .step-indicator {
        background: #556ee6;
        color: #fff;
    }

    .location-field-wrapper.completed-field .step-indicator {
        background: #34c38f;
        color: #fff;
    }

    .location-field-wrapper .form-select,
    .location-field-wrapper .form-control {
        border-radius: 8px;
    }

    .location-field-wrapper.active-field .form-select,
    .location-field-wrapper.active-field .form-control {
        border-color: #556ee6;
    }

    /* Step 6: Season Selection Styles */
    .step-6-content {
        padding: 20px;
    }

    .season-selection-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .season-selection-container .row {
        max-width: 850px;
        margin: 0 auto;
    }

    .season-selection-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 16px;
        padding: 30px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        width: 100%;
        max-width: 260px;
        min-height: 260px;
        margin: 0 auto;
        aspect-ratio: 1 / 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .season-selection-box:hover {
        border-color: #556ee6;
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(85, 110, 230, 0.15);
    }

    .season-selection-box.selected {
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.05) 0%, rgba(52, 195, 143, 0.1) 100%);
        box-shadow: 0 10px 30px rgba(52, 195, 143, 0.2);
        animation: selectPulse 0.6s ease-out;
    }

    .season-selection-box.selected .season-title {
        color: #34c38f;
    }

    .season-icon {
        width: 120px;
        height: 120px;
        margin: 0 auto 20px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        overflow: hidden;
    }

    .season-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .season-selection-box:hover .season-icon {
        transform: scale(1.1);
    }

    .season-selection-box.selected .season-icon {
        transform: scale(1.1);
    }

    .season-icon svg,
    .season-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .season-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
        transition: color 0.3s ease;
    }

    .season-subtitle {
        font-size: 0.9rem;
        color: #74788d;
        font-style: italic;
        margin-bottom: 3px;
    }

    .season-months {
        font-size: 0.8rem;
        color: #adb5bd;
        margin-bottom: 0;
    }

    .season-check {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 28px;
        height: 28px;
        background: #34c38f;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
    }

    .season-selection-box.selected .season-check {
        opacity: 1;
        transform: scale(1);
    }

    .season-selection-box.shake-animation {
        animation: shake 0.5s ease-in-out;
        border-color: #f46a6a !important;
    }

    /* Goal Selection Styles */
    .goal-selection-container {
        padding: 20px 0;
    }

    .goal-selection-container .row {
        max-width: 1000px;
        margin: 0 auto;
    }

    .goal-selection-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 16px;
        padding: 30px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        width: 100%;
        max-width: 300px;
        min-height: 320px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
    }

    .goal-selection-box:hover {
        border-color: #556ee6;
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(85, 110, 230, 0.15);
    }

    .goal-selection-box.selected {
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.05) 0%, rgba(52, 195, 143, 0.1) 100%);
        box-shadow: 0 10px 30px rgba(52, 195, 143, 0.2);
        animation: selectPulse 0.6s ease-out;
    }

    .goal-selection-box.selected .goal-title {
        color: #34c38f;
    }

    .goal-icon {
        width: 90px;
        height: 90px;
        margin: 0 auto 20px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .goal-selection-box:hover .goal-icon {
        transform: scale(1.1);
    }

    .goal-selection-box.selected .goal-icon {
        transform: scale(1.1);
    }

    .goal-icon svg {
        width: 100%;
        height: 100%;
    }

    .goal-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #495057;
        margin-bottom: 5px;
        transition: color 0.3s ease;
    }

    .goal-subtitle {
        font-size: 0.95rem;
        color: #74788d;
        font-style: italic;
        margin-bottom: 10px;
    }

    .goal-description {
        font-size: 0.85rem;
        color: #adb5bd;
        margin-bottom: 0;
        line-height: 1.4;
    }

    .goal-check {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #34c38f;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
    }

    .goal-check i {
        font-size: 1.1rem;
    }

    .goal-selection-box.selected .goal-check {
        opacity: 1;
        transform: scale(1);
    }

    .goal-selection-box.shake-animation {
        animation: shake 0.5s ease-in-out;
        border-color: #f46a6a !important;
    }

    /* Step 17: Leaf Symptoms Styles */
    .leaf-symptoms-container {
        padding: 10px 0;
    }

    .leaf-symptom-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 15px 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        min-height: 135px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .leaf-symptom-box:hover {
        border-color: #556ee6;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(85, 110, 230, 0.15);
    }

    .leaf-symptom-box.selected {
        border-color: #f1b44c;
        background: linear-gradient(135deg, rgba(241, 180, 76, 0.05) 0%, rgba(241, 180, 76, 0.1) 100%);
        box-shadow: 0 6px 20px rgba(241, 180, 76, 0.2);
    }

    .symptom-icon {
        margin-bottom: 8px;
        transition: transform 0.3s ease;
    }

    .leaf-symptom-box:hover .symptom-icon {
        transform: scale(1.1);
    }

    .symptom-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #495057;
        display: block;
    }

    .symptom-sublabel {
        font-size: 0.72rem;
        color: #6c757d;
        display: block;
        margin-top: 2px;
    }

    .symptom-info-btn {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 22px;
        height: 22px;
        background: #556ee6;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s ease;
        z-index: 2;
    }

    .symptom-info-btn:hover {
        background: #3b5bdb;
        transform: scale(1.1);
    }

    .symptom-checkbox {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 24px;
        height: 24px;
        background: #fff;
        border: 2px solid #dee2e6;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        transition: all 0.3s ease;
    }

    .symptom-checkbox i {
        font-size: 1rem;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.2s ease;
    }

    .leaf-symptom-box.selected .symptom-checkbox {
        background: #f1b44c;
        border-color: #f1b44c;
    }

    .leaf-symptom-box.selected .symptom-checkbox i {
        opacity: 1;
        transform: scale(1);
    }

    /* Step 18: Pest Selection Styles */
    .pest-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
    }

    .pest-section h5 {
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .pest-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 12px 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        min-height: 125px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .pest-box:hover {
        border-color: #f44336;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(244, 67, 54, 0.15);
    }

    .pest-box.selected {
        border-color: #f44336;
        background: linear-gradient(135deg, rgba(244, 67, 54, 0.05) 0%, rgba(244, 67, 54, 0.1) 100%);
        box-shadow: 0 6px 20px rgba(244, 67, 54, 0.2);
    }

    .pest-none-box:hover {
        border-color: #4caf50;
        box-shadow: 0 6px 20px rgba(76, 175, 80, 0.15);
    }

    .pest-none-box.selected {
        border-color: #4caf50;
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.05) 0%, rgba(76, 175, 80, 0.1) 100%);
        box-shadow: 0 6px 20px rgba(76, 175, 80, 0.2);
    }

    .pest-icon {
        margin-bottom: 6px;
        transition: transform 0.3s ease;
    }

    .pest-box:hover .pest-icon {
        transform: scale(1.1);
    }

    .pest-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #495057;
        display: block;
    }

    .pest-sublabel {
        font-size: 0.68rem;
        color: #6c757d;
        display: block;
        margin-top: 1px;
    }

    .pest-info-btn {
        position: absolute;
        top: 6px;
        left: 6px;
        width: 20px;
        height: 20px;
        background: #f44336;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s ease;
        z-index: 2;
    }

    .pest-info-btn:hover {
        background: #d32f2f;
        transform: scale(1.1);
    }

    .pest-checkbox {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 22px;
        height: 22px;
        background: #fff;
        border: 2px solid #dee2e6;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        transition: all 0.3s ease;
    }

    .pest-checkbox i {
        font-size: 0.9rem;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.2s ease;
    }

    .pest-box.selected .pest-checkbox {
        background: #f44336;
        border-color: #f44336;
    }

    .pest-none-box.selected .pest-checkbox {
        background: #4caf50;
        border-color: #4caf50;
    }

    .pest-box.selected .pest-checkbox i {
        opacity: 1;
        transform: scale(1);
    }

    /* Step 12: Soil Test Styles */
    .soil-test-answer-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 16px;
        padding: 20px 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .soil-test-answer-box:hover {
        border-color: #556ee6;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(85, 110, 230, 0.15);
    }

    .soil-test-answer-box.selected {
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.05) 0%, rgba(52, 195, 143, 0.1) 100%);
        box-shadow: 0 5px 20px rgba(52, 195, 143, 0.2);
    }

    .soil-test-check {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #34c38f;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.3s ease;
    }

    .soil-test-check i {
        color: #fff;
        font-size: 14px;
    }

    .soil-test-answer-box.selected .soil-test-check {
        opacity: 1;
        transform: scale(1);
    }

    .soil-test-answer-box.shake-animation {
        animation: shake 0.5s ease-in-out;
        border-color: #f46a6a !important;
    }

    #soil-test-encoding-section .card {
        border-radius: 10px;
        overflow: hidden;
    }

    #soil-test-encoding-section .card-header {
        border-bottom: 1px solid #e9ecef;
    }

    #soil-test-encoding-section .form-control,
    #soil-test-encoding-section .form-select {
        border-radius: 8px;
    }

    /* Step 19: Spray Approach Styles */
    .step-19-content {
        padding: 20px;
    }

    .spray-approach-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 16px;
        padding: 30px 20px 25px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .spray-approach-box:hover {
        border-color: #556ee6;
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(85, 110, 230, 0.15);
    }

    .spray-approach-box.selected {
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.05) 0%, rgba(52, 195, 143, 0.1) 100%);
        box-shadow: 0 10px 30px rgba(52, 195, 143, 0.2);
        animation: selectPulse 0.6s ease-out;
    }

    .spray-approach-box.selected .spray-title {
        color: #34c38f;
    }

    .spray-icon {
        width: 100px;
        height: 100px;
        margin: 0 auto 15px;
        transition: all 0.3s ease;
    }

    .spray-approach-box:hover .spray-icon {
        transform: scale(1.1);
    }

    .spray-approach-box.selected .spray-icon {
        transform: scale(1.1);
    }

    .spray-icon svg {
        width: 100%;
        height: 100%;
    }

    .spray-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 4px;
        transition: color 0.3s ease;
    }

    .spray-subtitle {
        font-size: 0.9rem;
        color: #74788d;
        margin-bottom: 8px;
    }

    .spray-desc {
        display: block;
        font-size: 0.8rem;
        line-height: 1.4;
        padding: 0 5px;
    }

    .spray-check {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 30px;
        height: 30px;
        background: #34c38f;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.2rem;
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
    }

    .spray-approach-box.selected .spray-check {
        opacity: 1;
        transform: scale(1);
    }

    .spray-approach-box.shake-animation {
        animation: shake 0.5s ease-in-out;
        border-color: #f46a6a !important;
    }

    /* Step 6: Yield History Styles */
    .yield-history-container {
        padding: 10px 0;
    }

    .yield-answer-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 16px;
        padding: 25px 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .yield-answer-box:hover {
        border-color: #556ee6;
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(85, 110, 230, 0.15);
    }

    .yield-answer-box.selected {
        border-color: #556ee6;
        background: linear-gradient(135deg, rgba(85, 110, 230, 0.05) 0%, rgba(85, 110, 230, 0.1) 100%);
        box-shadow: 0 10px 30px rgba(85, 110, 230, 0.2);
    }

    .yield-answer-icon {
        margin-bottom: 10px;
        transition: transform 0.3s ease;
    }

    .yield-answer-box:hover .yield-answer-icon {
        transform: scale(1.1);
    }

    .yield-answer-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 0;
    }

    .yield-answer-subtitle {
        font-size: 0.85rem;
        color: #74788d;
        margin-bottom: 0;
    }

    .yield-answer-check {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 24px;
        height: 24px;
        background: #34c38f;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.3s ease;
    }

    .yield-answer-box.selected .yield-answer-check {
        opacity: 1;
        transform: scale(1);
    }

    /* Reason boxes */
    .yield-reasons-container {
        padding: 10px 0;
    }

    .reason-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 16px;
        padding: 20px 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        min-height: 180px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .reason-box:hover {
        border-color: #556ee6;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(85, 110, 230, 0.15);
    }

    .reason-box.selected {
        border-color: #f1b44c;
        background: linear-gradient(135deg, rgba(241, 180, 76, 0.05) 0%, rgba(241, 180, 76, 0.1) 100%);
        box-shadow: 0 6px 20px rgba(241, 180, 76, 0.2);
    }

    .reason-icon {
        width: 120px;
        height: 120px;
        margin-bottom: 12px;
        transition: transform 0.3s ease;
        border-radius: 50%;
        overflow: hidden;
    }

    .reason-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .reason-box:hover .reason-icon {
        transform: scale(1.1);
    }

    .reason-label {
        font-size: 0.95rem;
        font-weight: 600;
        color: #495057;
    }

    .reason-check {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 24px;
        height: 24px;
        background: #f1b44c;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.3s ease;
    }

    .reason-check i {
        font-size: 0.9rem;
    }

    .reason-box.selected .reason-check {
        opacity: 1;
        transform: scale(1);
    }

    #yield-details-section input,
    #yield-details-section select {
        caret-color: #495057 !important;
    }

    #yield-details-section input:focus {
        caret-color: #556ee6 !important;
    }

    /* Step 7: Soil Type Selection Styles */
    .soil-selection-container {
        padding: 10px 0;
    }

    .soil-selection-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 15px 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        min-height: 140px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .soil-selection-box:hover {
        border-color: #8B4513;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(139, 69, 19, 0.15);
    }

    .soil-selection-box.selected {
        border-color: #8B4513;
        background: linear-gradient(135deg, rgba(139, 69, 19, 0.05) 0%, rgba(139, 69, 19, 0.1) 100%);
        box-shadow: 0 6px 20px rgba(139, 69, 19, 0.2);
    }

    .soil-icon {
        margin-bottom: 8px;
        transition: transform 0.3s ease;
    }

    .soil-selection-box:hover .soil-icon {
        transform: scale(1.1);
    }

    .soil-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 2px;
    }

    .soil-subtitle {
        font-size: 0.75rem;
        color: #74788d;
        margin-bottom: 0;
    }

    .soil-check {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 22px;
        height: 22px;
        background: #8B4513;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.3s ease;
    }

    .soil-check i {
        font-size: 0.85rem;
    }

    .soil-selection-box.selected .soil-check {
        opacity: 1;
        transform: scale(1);
    }

    /* Step 8: Soil Texture Styles */
    .texture-selection-container {
        padding: 10px 0;
    }

    .texture-selection-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        min-height: 160px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .texture-selection-box:hover {
        border-color: #8B4513;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(139, 69, 19, 0.15);
    }

    .texture-selection-box.selected {
        border-color: #8B4513;
        background: linear-gradient(135deg, rgba(139, 69, 19, 0.05) 0%, rgba(139, 69, 19, 0.1) 100%);
        box-shadow: 0 6px 20px rgba(139, 69, 19, 0.2);
    }

    .texture-icon {
        margin-bottom: 10px;
        transition: transform 0.3s ease;
    }

    .texture-selection-box:hover .texture-icon {
        transform: scale(1.1);
    }

    .texture-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 3px;
    }

    .texture-subtitle {
        font-size: 0.8rem;
        color: #74788d;
        margin-bottom: 0;
    }

    .texture-info-btn {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 24px;
        height: 24px;
        background: #556ee6;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        z-index: 5;
    }

    .texture-info-btn:hover {
        background: #3b5bdb;
        transform: scale(1.1);
    }

    .texture-check {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 22px;
        height: 22px;
        background: #8B4513;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.3s ease;
    }

    .texture-check i {
        font-size: 0.85rem;
    }

    .texture-selection-box.selected .texture-check {
        opacity: 1;
        transform: scale(1);
    }

    /* Step 9: pH Clue Styles */
    .ph-clue-container {
        padding: 10px 0;
        max-width: 700px;
        margin: 0 auto;
    }

    .ph-question-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        transition: all 0.3s ease;
        position: relative;
    }

    .ph-question-box:hover {
        border-color: #556ee6;
        box-shadow: 0 4px 15px rgba(85, 110, 230, 0.1);
    }

    .ph-question-box.has-answer {
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.03) 0%, rgba(52, 195, 143, 0.08) 100%);
    }

    .ph-question-row {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .ph-question-text {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .ph-question-label {
        font-size: 0.95rem;
        font-weight: 500;
        color: #495057;
        margin-bottom: 0;
    }

    .ph-info-btn {
        width: 22px;
        height: 22px;
        background: #556ee6;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .ph-info-btn:hover {
        background: #3b5bdb;
        transform: scale(1.1);
    }

    .ph-answer-options {
        display: flex;
        gap: 10px;
        flex-shrink: 0;
    }

    .ph-answer-btn {
        padding: 8px 20px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        background: #fff;
        font-weight: 500;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 70px;
    }

    .ph-answer-btn:hover {
        border-color: #556ee6;
        background: rgba(85, 110, 230, 0.05);
    }

    .ph-answer-btn.selected-yes {
        border-color: #f1b44c;
        background: linear-gradient(135deg, rgba(241, 180, 76, 0.1) 0%, rgba(241, 180, 76, 0.2) 100%);
        color: #b37a00;
    }

    .ph-answer-btn.selected-no {
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.1) 0%, rgba(52, 195, 143, 0.2) 100%);
        color: #1a8754;
    }

    /* Step 10: Soil Indicators (Multi-Select) Styles */
    .soil-indicators-container {
        padding: 10px 0;
    }

    .soil-indicator-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 15px 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        min-height: 130px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .soil-indicator-box:hover {
        border-color: #556ee6;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(85, 110, 230, 0.15);
    }

    .soil-indicator-box.selected {
        border-color: #556ee6;
        background: linear-gradient(135deg, rgba(85, 110, 230, 0.05) 0%, rgba(85, 110, 230, 0.1) 100%);
        box-shadow: 0 6px 20px rgba(85, 110, 230, 0.2);
    }

    .indicator-icon {
        margin-bottom: 8px;
        transition: transform 0.3s ease;
    }

    .soil-indicator-box:hover .indicator-icon {
        transform: scale(1.1);
    }

    .indicator-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #495057;
        display: block;
    }

    .indicator-sublabel {
        font-size: 0.75rem;
        color: #6c757d;
        display: block;
        margin-top: 2px;
    }

    .indicator-info-btn {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 22px;
        height: 22px;
        background: #556ee6;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s ease;
        z-index: 2;
    }

    .indicator-info-btn:hover {
        background: #3b5bdb;
        transform: scale(1.1);
    }

    .indicator-checkbox {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 24px;
        height: 24px;
        background: #fff;
        border: 2px solid #dee2e6;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        transition: all 0.3s ease;
    }

    .indicator-checkbox i {
        font-size: 1rem;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.2s ease;
    }

    .soil-indicator-box.selected .indicator-checkbox {
        background: #556ee6;
        border-color: #556ee6;
    }

    .soil-indicator-box.selected .indicator-checkbox i {
        opacity: 1;
        transform: scale(1);
    }

    /* Step 11: Drainage Styles */
    .drainage-selection-container {
        padding: 10px 0;
    }

    .drainage-selection-container .row {
        max-width: 850px;
        margin: 0 auto;
    }

    .drainage-selection-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 25px 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        width: 100%;
        max-width: 260px;
        min-height: 260px;
        margin: 0 auto;
        aspect-ratio: 1 / 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .drainage-selection-box:hover {
        border-color: #42A5F5;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(66, 165, 245, 0.15);
    }

    .drainage-selection-box.selected {
        border-color: #42A5F5;
        background: linear-gradient(135deg, rgba(66, 165, 245, 0.05) 0%, rgba(66, 165, 245, 0.1) 100%);
        box-shadow: 0 6px 20px rgba(66, 165, 245, 0.2);
    }

    .drainage-icon {
        margin-bottom: 12px;
        transition: transform 0.3s ease;
    }

    .drainage-selection-box:hover .drainage-icon {
        transform: scale(1.1);
    }

    .drainage-title {
        font-size: 1rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 3px;
    }

    .drainage-subtitle {
        font-size: 0.8rem;
        color: #74788d;
        margin-bottom: 0;
    }

    .drainage-info-btn {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 24px;
        height: 24px;
        background: #556ee6;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        z-index: 5;
    }

    .drainage-info-btn:hover {
        background: #3b5bdb;
        transform: scale(1.1);
    }

    .drainage-check {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 22px;
        height: 22px;
        background: #42A5F5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.3s ease;
    }

    .drainage-check i {
        font-size: 0.85rem;
    }

    .drainage-selection-box.selected .drainage-check {
        opacity: 1;
        transform: scale(1);
    }

    /* Step 11: New Soil Problems Styles (soil suspicion based) */
    .soil-suspicion-container {
        padding: 10px 0;
    }

    .suspicion-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        min-height: 160px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .suspicion-box:hover {
        border-color: #f1b44c;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(241, 180, 76, 0.15);
    }

    .suspicion-box.selected {
        border-color: #f1b44c;
        background: linear-gradient(135deg, rgba(241, 180, 76, 0.05) 0%, rgba(241, 180, 76, 0.1) 100%);
        box-shadow: 0 6px 20px rgba(241, 180, 76, 0.2);
    }

    .suspicion-icon {
        margin-bottom: 10px;
        transition: transform 0.3s ease;
    }

    .suspicion-box:hover .suspicion-icon {
        transform: scale(1.1);
    }

    .suspicion-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 3px;
    }

    .suspicion-subtitle {
        font-size: 0.75rem;
        color: #74788d;
        margin-bottom: 0;
        line-height: 1.3;
    }

    .suspicion-info-btn {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 24px;
        height: 24px;
        background: #556ee6;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        z-index: 5;
    }

    .suspicion-info-btn:hover {
        background: #3b5bdb;
        transform: scale(1.1);
    }

    .suspicion-check {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 22px;
        height: 22px;
        background: #f1b44c;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.3s ease;
    }

    .suspicion-check i {
        font-size: 0.85rem;
    }

    .suspicion-box.selected .suspicion-check {
        opacity: 1;
        transform: scale(1);
    }

    /* Info Modal Styles for New Steps */

    .soil-info-modal .modal-body {
        padding-bottom: 30px;
    }

    .soil-info-modal .modal-header {
        background: #5b7a3a;
        color: #fff;
    }

    .soil-info-modal .modal-header .modal-title {
        outline: none;
        user-select: none;
        cursor: default;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    .soil-info-modal .modal-header .btn-close {
        filter: invert(1);
    }

    .soil-info-modal .modal-title,
    .soil-info-modal .modal-header,
    .soil-info-modal .info-section,
    .soil-info-modal .info-section h6,
    .soil-info-modal .info-section p,
    .soil-info-modal .modal-body {
        outline: none;
        user-select: none;
        cursor: default;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    .soil-info-modal .info-section {
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .soil-info-modal .info-section .info-section-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        cursor: pointer;
        border: none;
        width: 100%;
        background: transparent;
        outline: none;
        user-select: none;
    }

    .soil-info-modal .info-section .info-section-toggle h6 {
        font-weight: 600;
        margin-bottom: 0;
        font-size: 0.9rem;
    }

    .soil-info-modal .info-section .info-section-toggle .toggle-icon {
        transition: transform 0.3s ease;
        font-size: 1.1rem;
    }

    .soil-info-modal .info-section .info-section-toggle.collapsed .toggle-icon {
        transform: rotate(-90deg);
    }

    .soil-info-modal .info-section .info-section-body {
        padding: 0 16px 14px 16px;
    }

    .soil-info-modal .info-section .info-section-body p {
        font-size: 0.88rem;
        line-height: 1.65;
        color: #495057;
        margin-bottom: 0;
    }

    .soil-info-modal .info-section.section-benefits {
        background: #e8f5e9;
    }

    .soil-info-modal .info-section.section-benefits h6 {
        color: #2e7d32;
    }

    .soil-info-modal .info-section.section-best-for {
        background: #fff3e0;
    }

    .soil-info-modal .info-section.section-best-for h6 {
        color: #e65100;
    }

    .soil-info-modal .info-section.section-tip {
        background: #e3f2fd;
    }

    .soil-info-modal .info-section.section-tip h6 {
        color: #1565c0;
    }

    .soil-info-modal .info-section.section-count {
        background: #f3e5f5;
    }

    .soil-info-modal .info-section.section-count h6 {
        color: #7b1fa2;
    }

    .soil-info-modal .info-signs {
        background: #FFF3E0;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
    }

    .soil-info-modal .info-signs h6 {
        color: #E65100;
        margin-bottom: 10px;
    }

    .soil-info-modal .info-image {
        border-radius: 8px;
        max-width: 100%;
        height: auto;
        margin: 10px 0;
        border: 2px solid #e9ecef;
    }

    .soil-info-modal .info-tip {
        background: #E8F5E9;
        border-radius: 8px;
        padding: 12px 15px;
        margin-top: 15px;
        border-left: 4px solid #4CAF50;
    }

    .soil-info-modal .info-tip h6 {
        color: #2E7D32;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    .soil-info-modal .info-tip p {
        color: #495057;
        margin-bottom: 0;
        font-size: 0.85rem;
    }

    /* Step 8: Soil Problems Styles (Old - to be replaced) */
    .soil-problems-container {
        padding: 10px 0;
    }

    .problem-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 15px 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        min-height: 130px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .problem-box:hover {
        border-color: #f1b44c;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(241, 180, 76, 0.15);
    }

    .problem-box.selected {
        border-color: #f1b44c;
        background: linear-gradient(135deg, rgba(241, 180, 76, 0.05) 0%, rgba(241, 180, 76, 0.1) 100%);
        box-shadow: 0 6px 20px rgba(241, 180, 76, 0.2);
    }

    .problem-icon {
        margin-bottom: 8px;
        transition: transform 0.3s ease;
    }

    .problem-box:hover .problem-icon {
        transform: scale(1.1);
    }

    .problem-label {
        font-size: 0.85rem;
        font-weight: 500;
        color: #495057;
        margin-bottom: 0;
    }

    .problem-info-btn {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 24px;
        height: 24px;
        background: #556ee6;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        z-index: 5;
    }

    .problem-info-btn:hover {
        background: #3b5bdb;
        transform: scale(1.1);
    }

    .problem-check {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 22px;
        height: 22px;
        background: #f1b44c;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.3s ease;
    }

    .problem-check i {
        font-size: 0.85rem;
    }

    .problem-box.selected .problem-check {
        opacity: 1;
        transform: scale(1);
    }

    /* Soil Problem Modal Styles */
    .soil-problem-modal .modal-header {
        background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
        color: #fff;
    }

    .soil-problem-modal .modal-header .btn-close {
        filter: invert(1);
    }

    .soil-problem-modal .problem-signs {
        background: #FFF3E0;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
    }

    .soil-problem-modal .problem-signs h6 {
        color: #E65100;
        margin-bottom: 10px;
    }

    .soil-problem-modal .problem-image {
        border-radius: 8px;
        max-width: 100%;
        height: auto;
        margin: 10px 0;
        border: 2px solid #e9ecef;
    }

    /* Step 9: Water & Irrigation Styles */
    .irrigation-container {
        padding: 10px 0;
    }

    .irrigation-section {
        margin-bottom: 30px;
    }

    .irrigation-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 15px;
        padding-left: 10px;
        border-left: 3px solid #556ee6;
    }

    .irrigation-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 15px 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .irrigation-box:hover {
        border-color: #556ee6;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(85, 110, 230, 0.15);
    }

    .irrigation-box.selected {
        border-color: #556ee6;
        background: linear-gradient(135deg, rgba(85, 110, 230, 0.05) 0%, rgba(85, 110, 230, 0.1) 100%);
        box-shadow: 0 6px 20px rgba(85, 110, 230, 0.2);
    }

    .irrigation-icon {
        margin-bottom: 8px;
        transition: transform 0.3s ease;
    }

    .irrigation-box:hover .irrigation-icon {
        transform: scale(1.1);
    }

    .irrigation-label {
        font-size: 0.9rem;
        font-weight: 500;
        color: #495057;
        margin-bottom: 0;
    }

    .irrigation-sublabel {
        font-size: 0.75rem;
        color: #74788d;
        margin-top: 3px;
    }

    .irrigation-check {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 22px;
        height: 22px;
        background: #556ee6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.3s ease;
    }

    .irrigation-check i {
        font-size: 0.85rem;
    }

    .irrigation-box.selected .irrigation-check {
        opacity: 1;
        transform: scale(1);
    }

    /* Step 11: Inclusion Selection Styles */
    .inclusion-selection-container {
        padding: 10px 0;
    }

    .inclusion-box {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 15px 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .inclusion-box:hover:not(.locked) {
        border-color: #556ee6;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(85, 110, 230, 0.15);
    }

    .inclusion-box.selected {
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.05) 0%, rgba(52, 195, 143, 0.1) 100%);
        box-shadow: 0 6px 20px rgba(52, 195, 143, 0.2);
    }

    .inclusion-box.locked {
        cursor: default;
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.08) 0%, rgba(52, 195, 143, 0.15) 100%);
    }

    .inclusion-box.locked .inclusion-label {
        color: #34c38f;
        font-weight: 600;
    }

    .inclusion-lock {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #34c38f;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
    }

    .inclusion-icon {
        width: 50px;
        height: 50px;
        margin-bottom: 8px;
        transition: transform 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .inclusion-box:hover:not(.locked) .inclusion-icon {
        transform: scale(1.1);
    }

    .inclusion-icon svg {
        width: 100%;
        height: 100%;
    }

    .inclusion-label {
        font-size: 0.8rem;
        font-weight: 500;
        color: #495057;
        line-height: 1.2;
        transition: color 0.3s ease;
    }

    .inclusion-box.selected .inclusion-label {
        color: #34c38f;
        font-weight: 600;
    }

    .inclusion-check {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #34c38f;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
        font-size: 0.85rem;
    }

    .inclusion-box.selected .inclusion-check {
        opacity: 1;
        transform: scale(1);
    }

    .variety-dropdown-section {
        max-width: 500px;
        margin: 0 auto;
        padding: 30px;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e9ecef;
    }

    .variety-dropdown-section .form-select {
        font-size: 1rem;
        padding: 12px 16px;
        border-radius: 8px;
    }

    .variety-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
    }

    .variety-info-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }

    .variety-info-item:last-child {
        margin-bottom: 0;
    }

    .variety-info-item i {
        width: 24px;
        color: #556ee6;
    }

    .variety-info-item span {
        color: #495057;
    }

    /* Searchable variety list */
    .variety-search-container {
        position: relative;
    }

    .variety-search-input {
        position: relative;
    }

    .variety-search-input input {
        padding-left: 40px;
        padding-right: 40px;
        height: 46px;
        border-radius: 8px;
        font-size: 0.95rem;
        caret-color: #495057 !important;
        cursor: text !important;
    }

    .variety-search-input input:focus {
        caret-color: #556ee6 !important;
        outline: none;
        border-color: #556ee6;
        box-shadow: 0 0 0 0.15rem rgba(85, 110, 230, 0.25);
    }

    .variety-search-input .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #74788d;
        font-size: 1.1rem;
    }

    .variety-search-input .clear-search {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
        cursor: pointer;
        font-size: 1.1rem;
        display: none;
    }

    .variety-search-input .clear-search:hover {
        color: #f46a6a;
    }

    .variety-list-container {
        max-height: 280px;
        overflow-y: auto;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-top: 10px;
        background: #fff;
    }

    .variety-list-container::-webkit-scrollbar {
        width: 6px;
    }

    .variety-list-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .variety-list-container::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .variety-list-container::-webkit-scrollbar-thumb:hover {
        background: #a1a1a1;
    }

    .variety-list-item {
        padding: 12px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f1f3f5;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .variety-list-item:last-child {
        border-bottom: none;
    }

    .variety-list-item:hover {
        background: #f8f9fa;
    }

    .variety-list-item.selected {
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.1) 0%, rgba(52, 195, 143, 0.15) 100%);
        border-left: 3px solid #34c38f;
    }

    .variety-list-item.selected .variety-item-name {
        color: #34c38f;
        font-weight: 600;
    }

    .variety-item-info {
        flex: 1;
    }

    .variety-item-name {
        font-weight: 500;
        color: #495057;
        margin-bottom: 2px;
    }

    .variety-item-meta {
        font-size: 0.8rem;
        color: #74788d;
    }

    .variety-item-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .variety-view-btn {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        background: #fff;
        color: #556ee6;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .variety-view-btn:hover {
        background: #556ee6;
        color: #fff;
        border-color: #556ee6;
        transform: scale(1.05);
    }

    .variety-view-btn i {
        font-size: 1rem;
    }

    .variety-item-check {
        color: #34c38f;
        font-size: 1.2rem;
        opacity: 0;
        transition: opacity 0.2s ease;
        flex-shrink: 0;
    }

    .variety-list-item.selected .variety-item-check {
        opacity: 1;
    }

    /* Variety Detail Modal Styles */
    #varietyDetailModal .modal-title,
    #varietyDetailModal .modal-header,
    #varietyDetailModal .nav-link,
    #varietyDetailModal .variety-detail-label,
    #varietyDetailModal .variety-detail-value,
    #varietyDetailModal .variety-detail-item,
    #varietyDetailModal .variety-detail-header,
    #varietyDetailModal .variety-detail-title,
    #varietyDetailModal .badge,
    #varietyDetailModal .tab-pane {
        outline: none;
        user-select: none;
        cursor: default;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    #varietyDetailModal .variety-detail-value a {
        cursor: pointer;
        user-select: text;
        -webkit-user-select: text;
        -moz-user-select: text;
        -ms-user-select: text;
    }

    .variety-detail-header {
        display: flex;
        align-items: center;
        gap: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 20px;
    }

    .variety-detail-image {
        width: 120px;
        height: 120px;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid #e9ecef;
        background: #f8f9fa;
    }

    .variety-detail-image-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 12px;
        border: 2px dashed #dee2e6;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
        font-size: 2.5rem;
    }

    .variety-detail-title h4 {
        margin-bottom: 5px;
        color: #495057;
    }

    .variety-detail-title .badge {
        font-size: 0.75rem;
    }

    .variety-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .variety-detail-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 12px 15px;
    }

    .variety-detail-item.full-width {
        grid-column: span 2;
    }

    .variety-detail-label {
        font-size: 0.75rem;
        color: #74788d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .variety-detail-value {
        font-size: 0.95rem;
        color: #495057;
        font-weight: 500;
    }

    .variety-detail-value a {
        color: #556ee6;
        text-decoration: none;
    }

    .variety-detail-value a:hover {
        text-decoration: underline;
    }

    .variety-detail-characteristics {
        white-space: pre-line;
        line-height: 1.6;
    }

    .variety-gene-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .variety-gene-badges .badge {
        font-weight: 500;
    }

    /* Modal Tabs Styling */
    .modal-tabs {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 0 15px;
    }

    .modal-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        color: #74788d;
        padding: 12px 20px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .modal-tabs .nav-link:hover {
        color: #556ee6;
        background: transparent;
    }

    .modal-tabs .nav-link.active {
        color: #556ee6;
        background: transparent;
        border-bottom-color: #556ee6;
    }

    /* Brochure Section in Details Tab */
    .brochure-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 15px;
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
        border: 1px solid #e9ecef;
        border-bottom: none;
    }

    .brochure-section-header h6 {
        color: #495057;
        font-weight: 600;
    }

    .brochure-actions {
        display: flex;
        gap: 8px;
    }

    .brochure-preview-container {
        border: 1px solid #e9ecef;
        border-radius: 0 0 8px 8px;
        overflow: hidden;
        background: #f8f9fa;
    }

    .brochure-pdf-frame {
        width: 100%;
        height: 400px;
        border: none;
    }

    /* Compare Tab Styling */
    .compare-search-header h6 {
        font-weight: 600;
    }

    .compare-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #556ee6;
        cursor: pointer;
        font-size: 0.9rem;
        padding: 8px 12px;
        background: #f0f4ff;
        border-radius: 6px;
        border: 1px solid #d4e0ff;
        transition: all 0.2s ease;
    }

    .compare-back-btn:hover {
        background: #e0e8ff;
        border-color: #556ee6;
    }

    /* Legacy Brochure Viewer Styling (keeping for reference) */
    .brochure-viewer-container {
        min-height: 500px;
        background: #f8f9fa;
    }

    .brochure-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 500px;
        color: #74788d;
    }

    .brochure-placeholder i {
        font-size: 4rem;
        color: #adb5bd;
        margin-bottom: 15px;
    }

    .brochure-toolbar {
        padding: 12px 20px;
        background: #fff;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        gap: 10px;
    }

    .brochure-pdf-frame {
        width: 100%;
        height: 500px;
        border: none;
    }

    /* Compare Tab Styling */
    .compare-container {
        min-height: 400px;
    }

    .compare-column {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        overflow: hidden;
        height: 100%;
    }

    .compare-column-primary {
        border-color: #34c38f;
    }

    .compare-column-header {
        padding: 12px 15px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .compare-column-body {
        padding: 15px;
        background: #fff;
        min-height: 350px;
    }

    .compare-search-results {
        max-height: 280px;
        overflow-y: auto;
        border: 1px solid #e9ecef;
        border-radius: 6px;
    }

    .compare-search-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        color: #74788d;
    }

    .compare-search-placeholder i {
        font-size: 2.5rem;
        color: #adb5bd;
        margin-bottom: 10px;
    }

    .compare-item {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f3f5;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .compare-item:last-child {
        border-bottom: none;
    }

    .compare-item:hover {
        background: #f8f9fa;
    }

    .compare-results-list {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #e9ecef;
        border-radius: 8px;
    }

    .compare-results-list .compare-item {
        padding: 12px 15px;
        border-bottom: 1px solid #f1f3f5;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .compare-results-list .compare-item:last-child {
        border-bottom: none;
    }

    .compare-results-list .compare-item:hover {
        background: #f0f4ff;
    }

    .compare-item-name {
        font-size: 0.95rem;
    }

    .compare-item-meta {
        font-size: 0.8rem;
    }

    .compare-item-name {
        font-weight: 500;
        color: #495057;
        font-size: 0.9rem;
    }

    .compare-item-meta {
        font-size: 0.75rem;
        color: #74788d;
    }

    .compare-variety-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
    }

    .compare-variety-card .variety-name {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }

    .compare-variety-card .variety-manufacturer {
        font-size: 0.85rem;
        color: #74788d;
        margin-bottom: 10px;
    }

    .compare-detail-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
        font-size: 0.85rem;
    }

    .compare-detail-row:last-child {
        border-bottom: none;
    }

    .compare-detail-label {
        color: #74788d;
    }

    .compare-detail-value {
        color: #495057;
        font-weight: 500;
        text-align: right;
    }

    .compare-detail-value.better {
        color: #34c38f;
    }

    .compare-detail-value.worse {
        color: #f46a6a;
    }

    .compare-change-btn {
        margin-top: 10px;
    }

    /* Back to Search Button */
    .back-to-search-btn {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #556ee6;
        cursor: pointer;
        font-size: 0.9rem;
        margin-bottom: 15px;
        padding: 8px 12px;
        background: #f0f4ff;
        border-radius: 6px;
        border: 1px solid #d4e0ff;
        transition: all 0.2s ease;
    }

    .back-to-search-btn:hover {
        background: #e0e8ff;
        border-color: #556ee6;
    }

    .variety-list-empty {
        padding: 30px 20px;
        text-align: center;
        color: #74788d;
    }

    .variety-list-empty i {
        font-size: 2.5rem;
        color: #adb5bd;
        margin-bottom: 10px;
    }

    .variety-list-loading {
        padding: 30px 20px;
        text-align: center;
        color: #74788d;
    }

    .variety-others-option {
        border-top: 2px dashed #ffe082;
        background: #fffbf0;
        margin-top: 5px;
    }

    .variety-others-option .variety-item-name {
        color: #e65100;
    }

    .variety-others-option .variety-item-meta {
        color: #ff9800;
    }

    .variety-others-option:hover {
        background: #fff8e1;
    }

    .variety-count-badge {
        font-size: 0.75rem;
        color: #74788d;
        margin-top: 8px;
    }

    .selected-variety-display {
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.08) 0%, rgba(52, 195, 143, 0.12) 100%);
        border: 1px solid rgba(52, 195, 143, 0.3);
        border-radius: 8px;
        padding: 12px 15px;
        margin-top: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .selected-variety-display .selected-info {
        display: flex;
        align-items: center;
    }

    .selected-variety-display .selected-info i {
        color: #34c38f;
        font-size: 1.3rem;
        margin-right: 10px;
    }

    .selected-variety-display .selected-name {
        font-weight: 600;
        color: #34c38f;
    }

    .selected-variety-display .selected-meta {
        font-size: 0.85rem;
        color: #74788d;
    }

    .selected-variety-display .btn-change {
        font-size: 0.8rem;
        padding: 4px 12px;
    }

    /* Manual entry section */
    .manual-entry-section {
        background: #fff8e1;
        border: 1px solid #ffe082;
        border-radius: 12px;
        padding: 20px;
        margin-top: 20px;
    }

    .manual-entry-section .form-label {
        font-weight: 500;
        color: #495057;
    }

    .manual-entry-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px dashed #ffe082;
    }

    .manual-entry-header i {
        font-size: 1.5rem;
        color: #ff9800;
        margin-right: 10px;
    }

    .manual-entry-header h6 {
        margin: 0;
        color: #e65100;
        font-weight: 600;
    }

    /* Variety Finder / Help Section */
    .variety-help-section {
        background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
        border: 1px solid #bbdefb;
        border-radius: 12px;
        padding: 20px;
        margin-top: 25px;
        margin-bottom: 20px;
        text-align: center;
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
    }

    .variety-help-section .help-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto 15px;
    }

    .variety-help-section .help-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .variety-help-section h6 {
        color: #495057;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .variety-help-section p {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }

    /* Variety Finder Wizard Modal */
    .finder-wizard-progress {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 25px;
    }

    .finder-wizard-progress .progress-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #e9ecef;
        transition: all 0.3s ease;
    }

    .finder-wizard-progress .progress-dot.active {
        background: #556ee6;
        transform: scale(1.2);
    }

    .finder-wizard-progress .progress-dot.completed {
        background: #34c38f;
    }

    .finder-step {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .finder-step.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Smooth section transition animations */
    .section-animated {
        transition: opacity 0.3s ease, transform 0.3s ease, max-height 0.4s ease;
    }

    .section-fade-in {
        animation: sectionFadeIn 0.35s ease forwards;
    }

    .section-fade-out {
        animation: sectionFadeOut 0.25s ease forwards;
    }

    .section-slide-down {
        animation: sectionSlideDown 0.35s ease forwards;
    }

    .section-slide-up {
        animation: sectionSlideUp 0.25s ease forwards;
    }

    @keyframes sectionFadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes sectionFadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }

    @keyframes sectionSlideDown {
        from {
            opacity: 0;
            max-height: 0;
            transform: translateY(-15px);
        }
        to {
            opacity: 1;
            max-height: 2000px;
            transform: translateY(0);
        }
    }

    @keyframes sectionSlideUp {
        from {
            opacity: 1;
            max-height: 2000px;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            max-height: 0;
            transform: translateY(-15px);
        }
    }

    .finder-step-header {
        text-align: center;
        margin-bottom: 25px;
    }

    .finder-step-header .step-number {
        display: inline-block;
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #556ee6 0%, #7c3aed 100%);
        color: #fff;
        border-radius: 50%;
        line-height: 36px;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .finder-step-header h5 {
        color: #495057;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .finder-step-header p {
        color: #74788d;
        font-size: 0.9rem;
        margin: 0;
    }

    .finder-option-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .finder-option-grid.single-col {
        grid-template-columns: 1fr;
    }

    .finder-option {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
    }

    .finder-option:hover {
        border-color: #556ee6;
        background: #f8f9ff;
    }

    .finder-option.selected {
        border-color: #34c38f;
        background: linear-gradient(135deg, rgba(52, 195, 143, 0.08) 0%, rgba(52, 195, 143, 0.15) 100%);
    }

    .finder-option .option-icon {
        font-size: 1.8rem;
        margin-bottom: 8px;
        color: #556ee6;
    }

    .finder-option.selected .option-icon {
        color: #34c38f;
    }

    .finder-option .option-title {
        font-weight: 600;
        color: #495057;
        font-size: 0.95rem;
        margin-bottom: 3px;
    }

    .finder-option .option-desc {
        font-size: 0.8rem;
        color: #74788d;
        margin: 0;
    }

    .finder-slider-container {
        padding: 10px 0;
    }

    .finder-slider-container label {
        display: block;
        font-weight: 500;
        color: #495057;
        margin-bottom: 10px;
    }

    .finder-slider-value {
        text-align: center;
        font-size: 1.2rem;
        font-weight: 600;
        color: #556ee6;
        margin-top: 10px;
    }

    .finder-checkbox-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .finder-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .finder-checkbox:hover {
        border-color: #556ee6;
    }

    .finder-checkbox.checked {
        border-color: #34c38f;
        background: rgba(52, 195, 143, 0.08);
    }

    .finder-checkbox input {
        display: none;
    }

    .finder-checkbox .checkbox-mark {
        width: 20px;
        height: 20px;
        border: 2px solid #ced4da;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .finder-checkbox.checked .checkbox-mark {
        background: #34c38f;
        border-color: #34c38f;
        color: #fff;
    }

    .finder-checkbox .checkbox-label {
        font-size: 0.9rem;
        color: #495057;
    }

    .finder-navigation {
        display: flex;
        justify-content: space-between;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #e9ecef;
    }

    /* Results Section */
    .finder-results {
        text-align: center;
    }

    .finder-results-loading {
        padding: 40px 20px;
    }

    .finder-results-loading i {
        font-size: 3rem;
        color: #556ee6;
        margin-bottom: 15px;
    }

    .finder-results-list {
        max-height: 350px;
        overflow-y: auto;
    }

    .finder-result-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .finder-result-item:hover {
        border-color: #556ee6;
        box-shadow: 0 4px 12px rgba(85, 110, 230, 0.15);
    }

    .finder-result-item .result-rank {
        width: 36px;
        height: 36px;
        background: #556ee6;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }

    .finder-result-item .result-rank.gold {
        background: #f9a825;
    }

    .finder-result-item .result-rank.silver {
        background: #78909c;
    }

    .finder-result-item .result-rank.bronze {
        background: #8d6e63;
    }

    .finder-result-item .result-image {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
        flex-shrink: 0;
    }

    .finder-result-item .result-image-placeholder {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .finder-result-item .result-info {
        flex-grow: 1;
        text-align: left;
    }

    .finder-result-item .result-name {
        font-weight: 600;
        color: #495057;
        margin-bottom: 2px;
    }

    .finder-result-item .result-meta {
        font-size: 0.85rem;
        color: #74788d;
    }

    .finder-result-item .result-match {
        color: #fff;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        flex-shrink: 0;
    }

    .finder-no-results {
        padding: 40px 20px;
        text-align: center;
    }

    .finder-no-results i {
        font-size: 3rem;
        color: #adb5bd;
        margin-bottom: 15px;
    }

    /* Free-text input section */
    .finder-freetext-section {
        margin-top: 15px;
    }

    .finder-freetext {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        font-size: 0.95rem;
        resize: none;
        transition: all 0.2s ease;
        caret-color: #556ee6 !important;
        user-select: text !important;
    }

    .finder-freetext:focus {
        border-color: #556ee6;
        box-shadow: 0 0 0 0.15rem rgba(85, 110, 230, 0.15);
        outline: none;
    }

    .finder-freetext::placeholder {
        color: #adb5bd;
        font-style: italic;
    }

    /* Skip button section */
    .finder-skip-section {
        margin-top: 20px;
        text-align: center;
        border-top: 1px dashed #e9ecef;
        padding-top: 15px;
    }

    .finder-skip-btn {
        font-size: 0.9rem;
        text-decoration: none;
        color: #74788d !important;
        transition: all 0.2s ease;
    }

    .finder-skip-btn:hover {
        color: #556ee6 !important;
        text-decoration: none;
    }

    /* Farm Size Input Section */
    .finder-input-section {
        padding: 15px 0;
    }

    .farm-size-input-wrapper,
    .farm-location-wrapper {
        max-width: 500px;
        margin: 0 auto;
    }

    .farm-size-group {
        max-width: 320px;
        margin: 0 auto;
    }

    .farm-size-input {
        font-size: 1.2rem;
        text-align: center;
        border-radius: 8px 0 0 8px !important;
        min-width: 140px;
    }

    .farm-size-unit {
        font-size: 0.95rem;
        border-radius: 0 8px 8px 0 !important;
        background-color: #f8f9fa;
        min-width: 140px;
    }

    .farm-size-helper {
        text-align: center;
    }

    .farm-size-preset {
        transition: all 0.2s ease;
    }

    .farm-size-preset:hover {
        background-color: #556ee6;
        border-color: #556ee6;
        color: #fff;
    }

    .farm-size-preset.active {
        background-color: #556ee6;
        border-color: #556ee6;
        color: #fff;
    }

    /* Farm Location Inputs */
    .farm-location-wrapper .form-label {
        margin-bottom: 6px;
    }

    .farm-location-wrapper .form-control {
        border-radius: 8px;
    }

    .farm-location-wrapper .form-control:focus {
        border-color: #556ee6;
        box-shadow: 0 0 0 0.15rem rgba(85, 110, 230, 0.15);
    }

    /* Season Selection Grid */
    .finder-option-grid.season-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }

    .season-option {
        padding: 20px 15px;
        text-align: center;
    }

    .season-option .option-icon {
        margin-bottom: 12px;
    }

    .season-option .season-icon {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .season-option .season-icon svg {
        max-width: 70px;
        max-height: 70px;
    }

    .season-option .option-title {
        font-size: 1rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }

    .season-option .option-desc {
        font-size: 0.8rem;
        color: #74788d;
    }

    .season-option.selected {
        border-color: #556ee6;
        background-color: #f0f4ff;
    }

    .season-option.selected .option-title {
        color: #556ee6;
    }

    @media (max-width: 576px) {
        .finder-option-grid.season-grid {
            grid-template-columns: 1fr;
        }

        .farm-size-group {
            flex-direction: column;
        }

        .farm-size-input,
        .farm-size-unit {
            border-radius: 8px !important;
        }

        .farm-size-input {
            margin-bottom: 10px;
        }
    }

    /* Smart Technician Loading Animation */
    .ai-loading-animation {
        margin-bottom: 15px;
    }

    .ai-loading-animation i {
        font-size: 4rem;
        color: #556ee6;
    }

    .ai-loading-animation .smart-tech-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        object-fit: cover;
        animation: techPulse 1.5s ease-in-out infinite;
    }

    @keyframes techPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }

    .step-number .smart-tech-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* AI Summary Box */
    .finder-ai-summary {
        margin-bottom: 15px;
    }

    .ai-summary-box {
        background: #f0f4ff;
        border: 1px solid #d6e0f5;
        border-radius: 10px;
        padding: 15px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        text-align: left;
    }

    .ai-summary-box i {
        font-size: 1.5rem;
        color: #556ee6;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .ai-summary-box p {
        margin: 0;
        color: #495057;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    /* AI Result Item with Reason */
    .finder-result-item .result-reason {
        font-size: 0.8rem;
        color: #495057;
        margin-top: 3px;
        font-style: normal;
    }

    /* =====================================================
       MOBILE RESPONSIVE STYLES WITH ANIMATIONS
       ===================================================== */

    /* Smooth transitions */
    .btn, .form-control, .form-select, .crop-selection-box, .method-option, .variety-item, .finder-result-item {
        transition: all 0.3s ease;
    }

    /* Card entrance animation */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: none;
        }
    }

    .card {
        animation: slideInUp 0.3s ease forwards;
    }

    /* Small monitors (1280px - 1400px) */
    @media (max-width: 1400px) {
        .wizard-progress-container {
            padding: 18px;
        }

        .wizard-step-content {
            padding: 35px 25px;
            min-height: 330px;
        }

        .crop-selection-box {
            padding: 25px 18px;
        }

        .crop-icon {
            width: 100px;
            height: 100px;
        }

        .crop-title {
            font-size: 15px;
        }

        .method-option {
            padding: 14px;
        }

        .method-icon {
            width: 42px;
            height: 42px;
            font-size: 1.3rem;
        }

        .variety-item {
            padding: 12px;
        }

        .variety-avatar {
            width: 52px;
            height: 52px;
        }
    }

    /* iPad landscape / 1024px monitors */
    @media (max-width: 1024px) {
        .wizard-progress-container {
            padding: 16px;
        }

        .wizard-step-content {
            padding: 28px 20px;
            min-height: 310px;
        }

        .wizard-step-content i {
            font-size: 3.5rem;
        }

        .wizard-step-content h5 {
            font-size: 15px;
        }

        .crop-selection-box {
            padding: 18px 14px;
            border-radius: 14px;
        }

        .crop-icon {
            width: 85px;
            height: 85px;
            margin-bottom: 15px;
        }

        .crop-title {
            font-size: 14px;
        }

        .method-option {
            padding: 12px 10px;
        }

        .method-icon {
            width: 38px;
            height: 38px;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .method-option h6 {
            font-size: 13px;
        }

        .method-option p {
            font-size: 11.5px;
        }

        .variety-item {
            padding: 10px;
        }

        .variety-avatar {
            width: 48px;
            height: 48px;
        }

        .variety-item h6 {
            font-size: 13.5px;
        }

        .variety-item small {
            font-size: 11.5px;
        }

        .wizard-navigation .btn {
            padding: 10px 20px;
            font-size: 13px;
        }

        /* Form elements */
        .form-label {
            font-size: 12.5px;
        }

        .form-control, .form-select {
            font-size: 13px;
            padding: 8px 10px;
        }

        /* Finder modal */
        .finder-category-btn {
            padding: 8px 14px;
            font-size: 12px;
        }

        .finder-result-item {
            padding: 10px 12px;
        }

        .finder-result-item h6 {
            font-size: 13px;
        }

        /* Step content */
        .step-4-content,
        .step-5-content,
        .step-6-content,
        .step-7-content {
            padding: 18px !important;
        }
    }

    /* Tablet Styles */
    @media (max-width: 991px) {
        .wizard-step-content {
            padding: 30px 20px;
            min-height: 300px;
        }

        .crop-selection-box {
            padding: 20px 15px;
        }

        .crop-icon {
            width: 90px;
            height: 90px;
        }

        .method-option {
            padding: 12px;
        }

        .method-icon {
            width: 40px;
            height: 40px;
            font-size: 1.25rem;
        }

        /* 2-column crop layout on tablet */
        .step-1-content .row > [class*="col-lg"] {
            flex: 0 0 50%;
            max-width: 50%;
        }

        /* 2-column method layout on tablet */
        .method-options-container .row > [class*="col-md"] {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }

    /* Mobile Styles */
    @media (max-width: 767px) {
        /* Wizard progress */
        .wizard-progress-container {
            padding: 15px;
        }

        .wizard-steps-indicator .step-label {
            width: 8px;
            height: 8px;
        }

        .wizard-steps-indicator .step-label.active {
            transform: scale(1.3);
        }

        /* Wizard step content */
        .wizard-step {
            min-height: 350px;
            padding: 15px 0;
        }

        .wizard-step-content {
            padding: 20px 15px;
            min-height: 280px;
        }

        .wizard-step-content i {
            font-size: 3rem;
        }

        .wizard-step-content h5 {
            font-size: 15px;
        }

        .wizard-step-content p {
            font-size: 13px;
        }

        /* Wizard navigation */
        .wizard-navigation {
            flex-wrap: wrap;
            gap: 10px;
            padding-top: 15px;
        }

        .wizard-navigation .btn {
            flex: 1;
            min-width: 120px;
        }

        /* Row container max-widths for mobile */
        .step-1-content .row,
        .step-2-content .row {
            max-width: 100%;
        }

        .season-selection-container .row,
        .drainage-selection-container .row {
            max-width: 100%;
        }

        .goal-selection-container .row {
            max-width: 100%;
        }

        /* Crop selection */
        .step-1-content {
            padding: 10px;
        }

        .crop-selection-box {
            padding: 15px 10px;
            border-radius: 12px;
            max-width: 160px;
            min-height: 160px;
        }

        .crop-icon {
            width: 70px;
            height: 70px;
            margin-bottom: 12px;
        }

        .crop-title {
            font-size: 14px;
        }

        /* Breed selection mobile */
        .breed-selection-box {
            padding: 15px 10px;
            border-radius: 12px;
            max-width: 160px;
            min-height: 160px;
        }

        .breed-icon {
            width: 70px;
            height: 70px;
            margin-bottom: 10px;
        }

        .breed-title {
            font-size: 14px;
        }

        .breed-subtitle {
            font-size: 11px;
        }

        /* Season selection mobile */
        .season-selection-box {
            padding: 15px 10px;
            border-radius: 12px;
            max-width: 150px;
            min-height: 150px;
        }

        .season-icon {
            width: 60px;
            height: 60px;
            margin-bottom: 10px;
        }

        .season-title {
            font-size: 13px;
        }

        .season-subtitle {
            font-size: 10px;
        }

        .season-months {
            font-size: 9px;
        }

        /* Goal selection mobile */
        .goal-selection-box {
            padding: 15px 10px;
            border-radius: 12px;
            max-width: 150px;
            min-height: 180px;
        }

        .goal-icon {
            width: 55px;
            height: 55px;
            margin-bottom: 10px;
        }

        .goal-icon svg {
            width: 55px;
            height: 55px;
        }

        .goal-title {
            font-size: 12px;
        }

        .goal-subtitle {
            font-size: 10px;
            margin-bottom: 5px;
        }

        .goal-description {
            font-size: 9px;
            line-height: 1.3;
        }

        .goal-check {
            width: 22px;
            height: 22px;
            top: 8px;
            right: 8px;
        }

        /* Drainage selection mobile */
        .drainage-selection-box {
            padding: 15px 10px;
            border-radius: 10px;
            max-width: 150px;
            min-height: 150px;
        }

        .drainage-icon svg {
            width: 50px;
            height: 50px;
        }

        .drainage-title {
            font-size: 12px;
        }

        .drainage-subtitle {
            font-size: 10px;
        }

        .drainage-info-btn {
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
        }

        .drainage-check {
            width: 20px;
            height: 20px;
        }

        /* Remove aspect-ratio on mobile to prevent layout issues */
        .crop-selection-box,
        .breed-selection-box,
        .season-selection-box,
        .drainage-selection-box {
            aspect-ratio: unset;
        }

        /* Planting method */
        .method-option {
            padding: 10px;
        }

        .method-icon {
            width: 35px;
            height: 35px;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .method-option h6 {
            font-size: 13px;
        }

        .method-option p {
            font-size: 11px;
        }

        /* Variety selection */
        .variety-item {
            padding: 10px;
        }

        .variety-item h6 {
            font-size: 14px;
        }

        .variety-item small {
            font-size: 11px;
        }

        .variety-avatar {
            width: 45px;
            height: 45px;
        }

        /* Variety finder modal */
        #varietyFinderModal .modal-body {
            padding: 15px;
        }

        .finder-category-btn {
            padding: 8px 12px;
            font-size: 12px;
        }

        .finder-result-item {
            padding: 10px;
        }

        .finder-result-item h6 {
            font-size: 13px;
        }

        .finder-result-item small {
            font-size: 11px;
        }

        /* Step content forms */
        .step-4-content,
        .step-5-content,
        .step-6-content,
        .step-7-content {
            padding: 15px !important;
        }

        /* Summary section */
        .summary-section {
            padding: 15px;
        }

        .summary-section h6 {
            font-size: 14px;
        }

        .summary-item {
            font-size: 13px;
        }

        /* AI avatar section */
        .ai-avatar-section .avatar-image {
            width: 50px !important;
            height: 50px !important;
        }

        /* Disclaimer section */
        .disclaimer-text {
            font-size: 11px;
        }

        /* Result section */
        .result-section {
            padding: 15px;
        }

        .result-section h5 {
            font-size: 15px;
        }

        .result-content {
            font-size: 14px;
        }
    }

    /* Small Mobile */
    @media (max-width: 575px) {
        .card-body {
            padding: 10px;
        }

        /* 2-column layout for selection boxes */
        .step-1-content .row > div,
        .step-2-content .row > div {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 5px;
        }

        /* Crop selection small mobile */
        .crop-selection-box {
            padding: 12px 8px;
            border-radius: 10px;
            max-width: 140px;
            min-height: 140px;
        }

        .crop-icon {
            width: 50px;
            height: 50px;
            margin-bottom: 8px;
        }

        .crop-title {
            font-size: 12px;
        }

        .crop-subtitle {
            font-size: 10px;
        }

        .crop-check {
            width: 22px;
            height: 22px;
            top: 8px;
            right: 8px;
            font-size: 0.9rem;
        }

        /* Breed selection small mobile */
        .breed-selection-box {
            padding: 12px 8px;
            border-radius: 10px;
            max-width: 140px;
            min-height: 140px;
        }

        .breed-icon {
            width: 50px;
            height: 50px;
            margin-bottom: 8px;
        }

        .breed-title {
            font-size: 12px;
        }

        .breed-subtitle {
            font-size: 10px;
        }

        .breed-check {
            width: 20px;
            height: 20px;
            top: 6px;
            right: 6px;
        }

        /* Season selection small mobile - 3 columns on small screens */
        .season-selection-container .row > div {
            flex: 0 0 33.333%;
            max-width: 33.333%;
            padding: 4px;
        }

        .season-selection-box {
            padding: 10px 6px;
            border-radius: 8px;
            max-width: 110px;
            min-height: 110px;
        }

        .season-icon {
            width: 40px;
            height: 40px;
            margin-bottom: 6px;
        }

        .season-title {
            font-size: 10px;
        }

        .season-subtitle {
            font-size: 8px;
        }

        .season-months {
            font-size: 7px;
        }

        .season-check {
            width: 18px;
            height: 18px;
            top: 5px;
            right: 5px;
            font-size: 10px;
        }

        /* Goal selection small mobile - compact 3 columns */
        .goal-selection-container .row > div {
            flex: 0 0 33.333%;
            max-width: 33.333%;
            padding: 4px;
        }

        .goal-selection-box {
            padding: 10px 6px;
            border-radius: 8px;
            max-width: 110px;
            min-height: 130px;
        }

        .goal-icon {
            width: 40px;
            height: 40px;
            margin-bottom: 6px;
        }

        .goal-icon svg {
            width: 40px;
            height: 40px;
        }

        .goal-title {
            font-size: 10px;
            margin-bottom: 2px;
        }

        .goal-subtitle {
            font-size: 8px;
            margin-bottom: 3px;
        }

        .goal-description {
            font-size: 7px;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .goal-check {
            width: 18px;
            height: 18px;
            top: 5px;
            right: 5px;
        }

        .goal-check i {
            font-size: 0.7rem;
        }

        /* Drainage selection small mobile - 3 columns */
        .drainage-selection-container .row > div {
            flex: 0 0 33.333%;
            max-width: 33.333%;
            padding: 4px;
        }

        .drainage-selection-box {
            padding: 10px 6px;
            border-radius: 8px;
            max-width: 110px;
            min-height: 110px;
        }

        .drainage-icon svg {
            width: 35px;
            height: 35px;
        }

        .drainage-title {
            font-size: 10px;
        }

        .drainage-subtitle {
            font-size: 8px;
        }

        .drainage-info-btn {
            width: 18px;
            height: 18px;
            font-size: 0.65rem;
            top: 5px;
            left: 5px;
        }

        .drainage-check {
            width: 18px;
            height: 18px;
            top: 5px;
            right: 5px;
        }

        /* Single column for methods */
        .method-options-container .row > div {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 10px;
        }

        /* Wizard content */
        .wizard-step-content {
            padding: 15px 10px;
            min-height: 250px;
        }

        /* Modal adjustments */
        .modal-dialog {
            margin: 8px;
        }

        .modal-lg {
            max-width: calc(100% - 16px);
        }

        .modal-body {
            padding: 12px;
        }

        .modal-footer {
            flex-wrap: wrap;
            gap: 8px;
        }

        .modal-footer .btn {
            flex: 1;
            min-width: 100px;
        }

        /* Variety finder categories */
        .finder-categories-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .finder-categories {
            flex-wrap: nowrap;
            min-width: max-content;
        }

        /* Alerts */
        .alert {
            padding: 12px;
            font-size: 13px;
        }

        /* Selected variety display mobile */
        .selected-variety-display {
            padding: 10px;
            flex-direction: column;
            gap: 8px;
            align-items: flex-start;
        }

        .selected-variety-display .selected-info {
            flex-direction: row;
            align-items: center;
        }

        .selected-variety-display .selected-info i {
            font-size: 1.1rem;
            margin-right: 8px;
        }

        .selected-variety-display .selected-name {
            font-size: 13px;
        }

        .selected-variety-display .selected-meta {
            font-size: 11px;
        }

        .selected-variety-display .btn-change {
            font-size: 11px;
            padding: 4px 10px;
        }

        /* Variety list items mobile */
        .variety-list-item {
            padding: 10px;
        }

        .variety-item-name {
            font-size: 13px;
        }

        .variety-item-meta {
            font-size: 11px;
        }

        /* Variety finder results mobile */
        .finder-result-item .variety-avatar {
            width: 35px;
            height: 35px;
        }

        /* Card header mobile */
        .card-header {
            padding: 12px;
        }

        .card-header h4 {
            font-size: 14px;
        }

        .card-header p {
            font-size: 12px;
        }

        .card-header .btn {
            font-size: 11px;
            padding: 6px 10px;
        }

        /* Leaf symptoms small mobile */
        .leaf-symptoms-container .row > div {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 4px;
        }

        .leaf-symptom-box {
            padding: 10px 6px;
            min-height: 100px;
        }

        .symptom-icon svg {
            width: 35px;
            height: 35px;
        }

        .symptom-label {
            font-size: 10px;
        }

        .symptom-sublabel {
            font-size: 8px;
        }

        /* Soil suspicion small mobile */
        .soil-suspicion-container .row > div {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 4px;
        }

        .suspicion-box {
            padding: 12px 8px;
            min-height: 120px;
        }

        .suspicion-icon svg {
            width: 35px;
            height: 35px;
        }

        .suspicion-label {
            font-size: 10px;
        }

        .suspicion-sublabel {
            font-size: 8px;
        }

        /* Planting system small mobile */
        .planting-system-container .row > div {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 4px;
        }

        .planting-system-box {
            padding: 12px 8px;
            min-height: 130px;
        }

        .planting-system-icon {
            width: 45px;
            height: 45px;
            margin-bottom: 8px;
        }

        .planting-system-title {
            font-size: 11px;
        }

        .planting-system-subtitle {
            font-size: 9px;
        }

        /* Remove aspect-ratio on small mobile to prevent overflow */
        .crop-selection-box,
        .breed-selection-box,
        .season-selection-box,
        .drainage-selection-box {
            aspect-ratio: unset;
        }
    }

    /* Extra Small Mobile (very small phones) */
    @media (max-width: 400px) {
        .card-body {
            padding: 8px;
        }

        /* Even smaller selection boxes */
        .crop-selection-box,
        .breed-selection-box {
            padding: 10px 6px;
            max-width: 120px;
            min-height: 120px;
        }

        .crop-icon,
        .breed-icon {
            width: 40px;
            height: 40px;
            margin-bottom: 6px;
        }

        .crop-title,
        .breed-title {
            font-size: 11px;
        }

        .crop-subtitle,
        .breed-subtitle {
            font-size: 9px;
        }

        /* Season/Drainage even smaller */
        .season-selection-box,
        .drainage-selection-box {
            padding: 8px 5px;
            max-width: 95px;
            min-height: 95px;
        }

        .season-icon,
        .drainage-icon {
            width: 32px;
            height: 32px;
            margin-bottom: 5px;
        }

        .drainage-icon svg {
            width: 30px;
            height: 30px;
        }

        .season-title,
        .drainage-title {
            font-size: 9px;
        }

        .season-subtitle,
        .drainage-subtitle {
            font-size: 7px;
        }

        .season-months {
            display: none;
        }

        /* Goal even smaller */
        .goal-selection-box {
            padding: 8px 5px;
            max-width: 95px;
            min-height: 110px;
        }

        .goal-icon {
            width: 32px;
            height: 32px;
            margin-bottom: 5px;
        }

        .goal-icon svg {
            width: 32px;
            height: 32px;
        }

        .goal-title {
            font-size: 9px;
        }

        .goal-subtitle {
            font-size: 7px;
        }

        .goal-description {
            display: none;
        }

        /* Wizard step content */
        .wizard-step-content {
            padding: 10px 8px;
        }

        .wizard-step-content h5 {
            font-size: 13px;
        }

        .wizard-step-content p {
            font-size: 11px;
        }

        /* Wizard navigation */
        .wizard-navigation {
            gap: 6px;
        }

        .wizard-navigation .btn {
            font-size: 12px;
            padding: 8px 12px;
            min-width: 90px;
        }

        /* Info buttons smaller */
        .drainage-info-btn,
        .symptom-info-btn {
            width: 16px;
            height: 16px;
            font-size: 0.55rem;
            top: 4px;
            left: 4px;
        }

        /* Check marks smaller */
        .crop-check,
        .breed-check,
        .season-check,
        .goal-check,
        .drainage-check {
            width: 16px;
            height: 16px;
            top: 4px;
            right: 4px;
            font-size: 0.65rem;
        }
    }

    /* Touch device */
    @media (hover: none) and (pointer: coarse) {
        .crop-selection-box:active {
            transform: scale(0.98);
            border-color: #556ee6;
        }

        .method-option:active {
            transform: scale(0.98);
        }

        .variety-item:active {
            background-color: #f8f9fa;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .finder-result-item:active {
            background-color: #e8f4fd;
        }
    }

    /* Loading animation */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .bx-spin, .bx-loader-alt {
        animation: spin 1s linear infinite;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Recommendations @endslot
        @slot('title') Create New Recommendation @endslot
    @endcomponent

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-0">
                        <i class="bx bx-plus-circle text-primary me-2"></i>Create New Recommendation
                    </h4>
                    <p class="text-secondary mb-0 mt-1">Bumuo ng rekomendasyon sa pamamagitan ng pagsagot sa mga tanong</p>
                </div>
                <a href="{{ route('recommendation-generate') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to Recommendations
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Wizard Progress -->
            <div class="wizard-progress-container">
                <div class="wizard-progress-bar">
                    <div class="progress-bar" role="progressbar" id="wizard-progress" style="width: 4.76%"></div>
                </div>
                <div class="wizard-steps-indicator">
                    <span class="step-label active" data-step="1"></span>
                    <span class="step-label" data-step="2"></span>
                    <span class="step-label" data-step="3"></span>
                    <span class="step-label" data-step="4"></span>
                    <span class="step-label" data-step="5"></span>
                    <span class="step-label" data-step="6"></span>
                    <span class="step-label" data-step="7"></span>
                    <span class="step-label" data-step="8"></span>
                    <span class="step-label" data-step="9"></span>
                    <span class="step-label" data-step="10"></span>
                    <span class="step-label" data-step="11"></span>
                    <span class="step-label" data-step="12"></span>
                    <span class="step-label" data-step="13"></span>
                    <span class="step-label" data-step="14"></span>
                    <span class="step-label" data-step="15"></span>
                    <span class="step-label" data-step="16"></span>
                    <span class="step-label" data-step="17"></span>
                    <span class="step-label" data-step="18"></span>
                    <span class="step-label" data-step="19"></span>
                    <span class="step-label" data-step="20"></span>
                    <span class="step-label" data-step="21"></span>
                </div>
            </div>

            <!-- Wizard Form -->
            <form id="recommendation-wizard-form" method="POST" novalidate>
                @csrf
                <div class="wizard-content">
                    <!-- Step 1: Crop Selection -->
                    <div class="wizard-step" id="step-1">
                        <div class="step-1-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Select Your Crop</h4>
                                <p class="text-secondary">Choose the crop you want to generate recommendations for</p>
                            </div>
                            <input type="hidden" name="crop_type" id="crop_type" value="">
                            <div class="row justify-content-center g-4">
                                <!-- Palay (Rice) Option -->
                                <div class="col-md-5 col-lg-4">
                                    <div class="crop-selection-box" data-crop="palay">
                                        <div class="crop-icon">
                                            <img src="{{ asset('images/recommendations/crop-methods/rice-image.webp') }}" alt="Palay (Rice)">
                                        </div>
                                        <h5 class="crop-title">Palay</h5>
                                        <p class="crop-subtitle">Rice</p>
                                        <div class="crop-check">
                                            <i class="bx bx-check"></i>
                                        </div>
                                    </div>
                                </div>
                                <!-- Corn (Mais) Option -->
                                <div class="col-md-5 col-lg-4">
                                    <div class="crop-selection-box" data-crop="corn">
                                        <div class="crop-icon">
                                            <img src="{{ asset('images/recommendations/crop-methods/corn-image.webp') }}" alt="Mais (Corn)">
                                        </div>
                                        <h5 class="crop-title">Mais</h5>
                                        <p class="crop-subtitle">Corn</p>
                                        <div class="crop-check">
                                            <i class="bx bx-check"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <small class="text-secondary" id="crop-selection-hint">
                                    <i class="bx bx-info-circle me-1"></i>Click on a crop to select it
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Breed Type and Variety Selection -->
                    <div class="wizard-step d-none" id="step-2">
                        <div class="step-2-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2" id="step2-title">Pumili ng Uri ng Binhi</h4>
                                <p class="text-secondary" id="step2-subtitle">Piliin ang uri ng binhi para sa iyong napiling pananim</p>
                            </div>
                            <input type="hidden" name="breed_type" id="breed_type" value="">
                            <input type="hidden" name="corn_type" id="corn_type" value="">
                            <input type="hidden" name="variety_id" id="variety_id" value="">

                            <!-- Rice Breed Type Selection (Inbred / Hybrid) -->
                            <div id="rice-breed-section" class="d-none">
                                <div class="row justify-content-center g-4 mb-4">
                                    <div class="col-md-5 col-lg-4">
                                        <div class="breed-selection-box" data-breed="inbred" data-crop="rice">
                                            <div class="breed-icon">
                                                <img src="{{ asset('images/recommendations/crop-methods/inbred-rice.webp') }}" alt="Inbred Rice">
                                            </div>
                                            <h5 class="breed-title">Inbred</h5>
                                            <p class="breed-subtitle">Tradisyonal na binhi</p>
                                            <div class="breed-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5 col-lg-4">
                                        <div class="breed-selection-box" data-breed="hybrid" data-crop="rice">
                                            <div class="breed-icon">
                                                <img src="{{ asset('images/recommendations/crop-methods/hybrid-rice.webp') }}" alt="Hybrid Rice">
                                            </div>
                                            <h5 class="breed-title">Hybrid</h5>
                                            <p class="breed-subtitle">Genetically Modified na binhi</p>
                                            <div class="breed-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Corn Type Selection (Yellow / White) -->
                            <div id="corn-breed-section" class="d-none">
                                <div class="row justify-content-center g-4 mb-4">
                                    <div class="col-md-5 col-lg-4">
                                        <div class="breed-selection-box" data-corn-type="yellow" data-crop="corn">
                                            <div class="breed-icon">
                                                <img src="{{ asset('images/recommendations/crop-methods/yellow-corns.webp') }}" alt="Yellow Corn">
                                            </div>
                                            <h5 class="breed-title">Yellow Corn</h5>
                                            <p class="breed-subtitle">Para sa Feeds</p>
                                            <div class="breed-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5 col-lg-4">
                                        <div class="breed-selection-box" data-corn-type="white" data-crop="corn">
                                            <div class="breed-icon">
                                                <img src="{{ asset('images/recommendations/crop-methods/white-corns.webp') }}" alt="White Corn">
                                            </div>
                                            <h5 class="breed-title">White Corn</h5>
                                            <p class="breed-subtitle">Pagkain ng Tao</p>
                                            <div class="breed-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Variety Search & Selection -->
                            <div id="variety-section" class="d-none mt-4">
                                <div class="variety-dropdown-section">
                                    <label class="form-label text-dark fw-semibold mb-3">
                                        <i class="bx bx-search-alt me-1"></i>Select Variety
                                    </label>

                                    <!-- Selected Variety Display (shown when a variety is selected) -->
                                    <div id="selected-variety-display" class="selected-variety-display d-none">
                                        <div class="selected-info">
                                            <i class="bx bx-check-circle"></i>
                                            <div>
                                                <div class="selected-name" id="selected-variety-name">-</div>
                                                <div class="selected-meta" id="selected-variety-meta">-</div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary btn-change" id="change-variety-btn">
                                            <i class="bx bx-refresh me-1"></i>Change
                                        </button>
                                    </div>

                                    <!-- Search Container (shown when selecting) -->
                                    <div id="variety-search-container" class="variety-search-container">
                                        <div class="variety-search-input">
                                            <i class="bx bx-search search-icon"></i>
                                            <input type="text" class="form-control" id="variety_search" placeholder="Type to search varieties..." autocomplete="off">
                                            <i class="bx bx-x clear-search" id="clear-variety-search"></i>
                                        </div>

                                        <!-- Variety List -->
                                        <div class="variety-list-container" id="variety-list-container">
                                            <div class="variety-list-loading">
                                                <i class="bx bx-loader-alt bx-spin" style="font-size: 2rem;"></i>
                                                <p class="mb-0 mt-2">Loading varieties...</p>
                                            </div>
                                        </div>

                                        <div class="variety-count-badge" id="variety-count">
                                            <i class="bx bx-info-circle me-1"></i><span>0 varieties available</span>
                                        </div>
                                    </div>

                                    <input type="hidden" name="variety_id" id="variety_id" value="">

                                    <!-- Manual Entry Section (for "Others") -->
                                    <div id="manual-entry-section" class="manual-entry-section d-none">
                                        <div class="back-to-search-btn" id="manual-back-to-search">
                                            <i class="bx bx-arrow-back"></i>
                                            <span>Back to Variety Search</span>
                                        </div>
                                        <div class="manual-entry-header">
                                            <i class="bx bx-edit-alt"></i>
                                            <h6>Enter Variety Details Manually</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="manual_variety_name" class="form-label">Variety Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="manual_variety_name" name="manual_variety_name" placeholder="e.g., RC 222, NK6414">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="manual_manufacturer" class="form-label">Manufacturer / Developer</label>
                                                <input type="text" class="form-control" id="manual_manufacturer" name="manual_manufacturer" placeholder="e.g., PhilRice, Syngenta">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="manual_yield" class="form-label">Potential Yield</label>
                                                <input type="text" class="form-control" id="manual_yield" name="manual_yield" placeholder="e.g., 6-8 tons/ha">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="manual_maturity" class="form-label">Days to Maturity</label>
                                                <input type="text" class="form-control" id="manual_maturity" name="manual_maturity" placeholder="e.g., 110-115 days">
                                            </div>
                                            <div class="col-12">
                                                <label for="manual_characteristics" class="form-label">Characteristics / Notes</label>
                                                <textarea class="form-control" id="manual_characteristics" name="manual_characteristics" rows="2" placeholder="Any additional details about this variety..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Need Help Choosing Section -->
                                <div class="variety-help-section">
                                    <div class="help-icon">
                                        <img src="{{ $avatarSettings->avatar_url }}" alt="Smart Technician">
                                    </div>
                                    <h6>Hindi sigurado kung anong variety ang pipiliin?</h6>
                                    <p>Sumagot ng ilang tanong at irerekomenda namin ang pinakamainam na variety para sa iyong bukid.</p>
                                    <button type="button" class="btn btn-primary" id="open-variety-finder">
                                        <i class="bx bx-magic me-1"></i>Hanapin ang Tamang Variety
                                    </button>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <small class="text-secondary" id="breed-selection-hint">
                                    <i class="bx bx-info-circle me-1"></i>Select a breed type to continue
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Planting/Cropping System -->
                    <div class="wizard-step d-none" id="step-3">
                        <div class="step-3-content">
                            <input type="hidden" name="rice_planting_system" id="rice_planting_system" value="">
                            <input type="hidden" name="corn_planting_system" id="corn_planting_system" value="">

                            <!-- Rice Planting System Section -->
                            <div id="rice-planting-section" class="d-none">
                                <div class="text-center mb-4">
                                    <h4 class="text-dark mb-2">Paano mo Itatanim ang Palay?</h4>
                                    <p class="text-secondary">Select your rice planting system</p>
                                </div>

                                <div class="planting-system-container">
                                    <div class="row justify-content-center g-4">
                                        <!-- Transplanted -->
                                        <div class="col-md-4">
                                            <div class="planting-system-box" data-system="transplanted" data-crop="rice">
                                                <button type="button" class="planting-system-info-btn" data-bs-toggle="modal" data-bs-target="#transplantedInfoModal" onclick="event.stopPropagation();">
                                                    <i class="bx bx-question-mark"></i>
                                                </button>
                                                <div class="planting-system-icon">
                                                    <img src="{{ asset('images/recommendations/crop-methods/rice-transplant-min.webp') }}" alt="Transplanted Rice">
                                                </div>
                                                <h5 class="planting-system-title">Transplanted</h5>
                                                <p class="planting-system-subtitle">Inilipat-tanim</p>
                                                <p class="planting-system-desc">Seedlings grown in seedbed, then transplanted to main field</p>
                                                <div class="planting-system-check">
                                                    <i class="bx bx-check"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Direct Seeding - Wet -->
                                        <div class="col-md-4">
                                            <div class="planting-system-box" data-system="direct_wet" data-crop="rice">
                                                <button type="button" class="planting-system-info-btn" data-bs-toggle="modal" data-bs-target="#directWetInfoModal" onclick="event.stopPropagation();">
                                                    <i class="bx bx-question-mark"></i>
                                                </button>
                                                <div class="planting-system-icon">
                                                    <img src="{{ asset('images/recommendations/crop-methods/rice-wet-direct-seeding-min.webp') }}" alt="Direct Wet Seeding Rice">
                                                </div>
                                                <h5 class="planting-system-title">Direct Seeding - Wet</h5>
                                                <p class="planting-system-subtitle">Sabog sa Basang Lupa</p>
                                                <p class="planting-system-desc">Seeds broadcasted on wet/puddled field</p>
                                                <div class="planting-system-check">
                                                    <i class="bx bx-check"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Direct Seeding - Dry -->
                                        <div class="col-md-4">
                                            <div class="planting-system-box" data-system="direct_dry" data-crop="rice">
                                                <button type="button" class="planting-system-info-btn" data-bs-toggle="modal" data-bs-target="#directDryInfoModal" onclick="event.stopPropagation();">
                                                    <i class="bx bx-question-mark"></i>
                                                </button>
                                                <div class="planting-system-icon">
                                                    <img src="{{ asset('images/recommendations/crop-methods/rice-dry-direct-seeding-min.webp') }}" alt="Direct Dry Seeding Rice">
                                                </div>
                                                <h5 class="planting-system-title">Direct Seeding - Dry</h5>
                                                <p class="planting-system-subtitle">Sabog sa Tuyong Lupa</p>
                                                <p class="planting-system-desc">Seeds sown on dry unpuddled field</p>
                                                <div class="planting-system-check">
                                                    <i class="bx bx-check"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Corn Planting System Section -->
                            <div id="corn-planting-section" class="d-none">
                                <div class="text-center mb-4">
                                    <h4 class="text-dark mb-2">Paano ang Pagtataniman ng Mais?</h4>
                                    <p class="text-secondary">Select your corn planting system</p>
                                </div>

                                <div class="planting-system-container">
                                    <div class="row justify-content-center g-4">
                                        <!-- Single Row -->
                                        <div class="col-md-5">
                                            <div class="planting-system-box" data-system="single_row" data-crop="corn">
                                                <button type="button" class="planting-system-info-btn" data-bs-toggle="modal" data-bs-target="#singleRowInfoModal" onclick="event.stopPropagation();">
                                                    <i class="bx bx-question-mark"></i>
                                                </button>
                                                <div class="planting-system-icon">
                                                    <img src="{{ asset('images/recommendations/srow-corn.png') }}" alt="Single Row Corn">
                                                </div>
                                                <h5 class="planting-system-title">Single Row</h5>
                                                <p class="planting-system-subtitle">Isahang Hanay</p>
                                                <p class="planting-system-desc">Traditional single row planting, wider spacing between rows</p>
                                                <div class="planting-system-check">
                                                    <i class="bx bx-check"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Double Row -->
                                        <div class="col-md-5">
                                            <div class="planting-system-box" data-system="double_row" data-crop="corn">
                                                <button type="button" class="planting-system-info-btn" data-bs-toggle="modal" data-bs-target="#doubleRowInfoModal" onclick="event.stopPropagation();">
                                                    <i class="bx bx-question-mark"></i>
                                                </button>
                                                <div class="planting-system-icon">
                                                    <img src="{{ asset('images/recommendations/drow-corn.png') }}" alt="Double Row Corn">
                                                </div>
                                                <h5 class="planting-system-title">Double Row</h5>
                                                <p class="planting-system-subtitle">Dalawahang Hanay</p>
                                                <p class="planting-system-desc">Two rows close together, then gap, higher plant density</p>
                                                <div class="planting-system-check">
                                                    <i class="bx bx-check"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <small class="text-secondary" id="planting-system-hint">
                                    <i class="bx bx-info-circle me-1"></i>Select a planting system to continue
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Farm Size -->
                    <div class="wizard-step d-none" id="step-4">
                        <div class="step-4-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Gaano Kalaki ang Bukid Mo?</h4>
                                <p class="text-secondary">Ilagay ang sukat ng lupang itatanim para sa cropping schedule na ito</p>
                            </div>
                            <input type="hidden" name="farm_size" id="farm_size" value="">
                            <input type="hidden" name="farm_unit" id="farm_unit" value="sqm">

                            <div class="farm-size-container">
                                <div class="row justify-content-center">
                                    <div class="col-md-10 col-lg-8">
                                        <div class="d-flex justify-content-center gap-3 mb-4">
                                            <input type="number" class="form-control form-control-lg text-center" id="farm_size_input"
                                                   placeholder="Sukat" step="1" min="0" max="999999" style="font-size: 1.3rem; height: 60px; max-width: 160px;">
                                            <select class="form-select form-select-lg" id="farm_unit_select" style="height: 60px; max-width: 165px;">
                                                <option value="sqm" selected>sqm</option>
                                                <option value="hectares">hectares</option>
                                            </select>
                                        </div>

                                        <!-- Quick Select Buttons -->
                                        <div class="text-center mb-4">
                                            <p class="text-secondary small mb-3">Pumili ng karaniwang sukat:</p>
                                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                                <button type="button" class="btn btn-outline-primary farm-preset-btn" data-size="500" data-unit="sqm">500 sqm</button>
                                                <button type="button" class="btn btn-outline-primary farm-preset-btn" data-size="1000" data-unit="sqm">1,000 sqm</button>
                                                <button type="button" class="btn btn-outline-primary farm-preset-btn" data-size="2500" data-unit="sqm">2,500 sqm</button>
                                                <button type="button" class="btn btn-outline-primary farm-preset-btn" data-size="5000" data-unit="sqm">5,000 sqm</button>
                                                <button type="button" class="btn btn-outline-primary farm-preset-btn" data-size="1" data-unit="hectares">1 hectare</button>
                                                <button type="button" class="btn btn-outline-primary farm-preset-btn" data-size="2" data-unit="hectares">2 hectares</button>
                                                <button type="button" class="btn btn-outline-primary farm-preset-btn" data-size="5" data-unit="hectares">5 hectares</button>
                                                <button type="button" class="btn btn-outline-primary farm-preset-btn" data-size="10" data-unit="hectares">10 hectares</button>
                                            </div>
                                        </div>

                                        <div class="alert alert-light border text-center">
                                            <i class="bx bx-info-circle text-primary me-1"></i>
                                            <small class="text-secondary">1 hectare = 10,000 square meters (sqm)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Farm Location -->
                    <div class="wizard-step d-none" id="step-5">
                        <div class="step-5-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Saan Matatagpuan ang Bukid Mo?</h4>
                                <p class="text-secondary">Ilagay ang lokasyon ng bukid mo para sa mas tumpak na rekomendasyon</p>
                            </div>
                            <input type="hidden" name="province" id="province" value="">
                            <input type="hidden" name="municipality" id="municipality" value="">

                            <div class="farm-location-container">
                                <div class="row justify-content-center">
                                    <div class="col-md-10 col-lg-8">
                                        <!-- Province Dropdown -->
                                        <div class="mb-4 location-field-wrapper active-field" id="province-wrapper">
                                            <label for="province_select" class="form-label text-dark fw-semibold">
                                                <span class="step-indicator">1</span>
                                                <i class="bx bx-map text-primary me-1"></i>Probinsya <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select form-select-lg" id="province_select">
                                                <option value="">-- Pumili ng Probinsya --</option>
                                                <!-- Luzon -->
                                                <optgroup label="Luzon - Ilocos Region">
                                                    <option value="Ilocos Norte">Ilocos Norte</option>
                                                    <option value="Ilocos Sur">Ilocos Sur</option>
                                                    <option value="La Union">La Union</option>
                                                    <option value="Pangasinan">Pangasinan</option>
                                                </optgroup>
                                                <optgroup label="Luzon - Cagayan Valley">
                                                    <option value="Batanes">Batanes</option>
                                                    <option value="Cagayan">Cagayan</option>
                                                    <option value="Isabela">Isabela</option>
                                                    <option value="Nueva Vizcaya">Nueva Vizcaya</option>
                                                    <option value="Quirino">Quirino</option>
                                                </optgroup>
                                                <optgroup label="Luzon - Central Luzon">
                                                    <option value="Aurora">Aurora</option>
                                                    <option value="Bataan">Bataan</option>
                                                    <option value="Bulacan">Bulacan</option>
                                                    <option value="Nueva Ecija">Nueva Ecija</option>
                                                    <option value="Pampanga">Pampanga</option>
                                                    <option value="Tarlac">Tarlac</option>
                                                    <option value="Zambales">Zambales</option>
                                                </optgroup>
                                                <optgroup label="Luzon - CALABARZON">
                                                    <option value="Batangas">Batangas</option>
                                                    <option value="Cavite">Cavite</option>
                                                    <option value="Laguna">Laguna</option>
                                                    <option value="Quezon">Quezon</option>
                                                    <option value="Rizal">Rizal</option>
                                                </optgroup>
                                                <optgroup label="Luzon - MIMAROPA">
                                                    <option value="Marinduque">Marinduque</option>
                                                    <option value="Occidental Mindoro">Occidental Mindoro</option>
                                                    <option value="Oriental Mindoro">Oriental Mindoro</option>
                                                    <option value="Palawan">Palawan</option>
                                                    <option value="Romblon">Romblon</option>
                                                </optgroup>
                                                <optgroup label="Luzon - Bicol Region">
                                                    <option value="Albay">Albay</option>
                                                    <option value="Camarines Norte">Camarines Norte</option>
                                                    <option value="Camarines Sur">Camarines Sur</option>
                                                    <option value="Catanduanes">Catanduanes</option>
                                                    <option value="Masbate">Masbate</option>
                                                    <option value="Sorsogon">Sorsogon</option>
                                                </optgroup>
                                                <!-- Visayas -->
                                                <optgroup label="Visayas - Western Visayas">
                                                    <option value="Aklan">Aklan</option>
                                                    <option value="Antique">Antique</option>
                                                    <option value="Capiz">Capiz</option>
                                                    <option value="Guimaras">Guimaras</option>
                                                    <option value="Iloilo">Iloilo</option>
                                                    <option value="Negros Occidental">Negros Occidental</option>
                                                </optgroup>
                                                <optgroup label="Visayas - Central Visayas">
                                                    <option value="Bohol">Bohol</option>
                                                    <option value="Cebu">Cebu</option>
                                                    <option value="Negros Oriental">Negros Oriental</option>
                                                    <option value="Siquijor">Siquijor</option>
                                                </optgroup>
                                                <optgroup label="Visayas - Eastern Visayas">
                                                    <option value="Biliran">Biliran</option>
                                                    <option value="Eastern Samar">Eastern Samar</option>
                                                    <option value="Leyte">Leyte</option>
                                                    <option value="Northern Samar">Northern Samar</option>
                                                    <option value="Samar">Samar</option>
                                                    <option value="Southern Leyte">Southern Leyte</option>
                                                </optgroup>
                                                <!-- Mindanao -->
                                                <optgroup label="Mindanao - Zamboanga Peninsula">
                                                    <option value="Zamboanga del Norte">Zamboanga del Norte</option>
                                                    <option value="Zamboanga del Sur">Zamboanga del Sur</option>
                                                    <option value="Zamboanga Sibugay">Zamboanga Sibugay</option>
                                                </optgroup>
                                                <optgroup label="Mindanao - Northern Mindanao">
                                                    <option value="Bukidnon">Bukidnon</option>
                                                    <option value="Camiguin">Camiguin</option>
                                                    <option value="Lanao del Norte">Lanao del Norte</option>
                                                    <option value="Misamis Occidental">Misamis Occidental</option>
                                                    <option value="Misamis Oriental">Misamis Oriental</option>
                                                </optgroup>
                                                <optgroup label="Mindanao - Davao Region">
                                                    <option value="Davao de Oro">Davao de Oro</option>
                                                    <option value="Davao del Norte">Davao del Norte</option>
                                                    <option value="Davao del Sur">Davao del Sur</option>
                                                    <option value="Davao Occidental">Davao Occidental</option>
                                                    <option value="Davao Oriental">Davao Oriental</option>
                                                </optgroup>
                                                <optgroup label="Mindanao - SOCCSKSARGEN">
                                                    <option value="Cotabato">Cotabato</option>
                                                    <option value="Sarangani">Sarangani</option>
                                                    <option value="South Cotabato">South Cotabato</option>
                                                    <option value="Sultan Kudarat">Sultan Kudarat</option>
                                                </optgroup>
                                                <optgroup label="Mindanao - Caraga">
                                                    <option value="Agusan del Norte">Agusan del Norte</option>
                                                    <option value="Agusan del Sur">Agusan del Sur</option>
                                                    <option value="Dinagat Islands">Dinagat Islands</option>
                                                    <option value="Surigao del Norte">Surigao del Norte</option>
                                                    <option value="Surigao del Sur">Surigao del Sur</option>
                                                </optgroup>
                                                <optgroup label="Mindanao - BARMM">
                                                    <option value="Basilan">Basilan</option>
                                                    <option value="Lanao del Sur">Lanao del Sur</option>
                                                    <option value="Maguindanao del Norte">Maguindanao del Norte</option>
                                                    <option value="Maguindanao del Sur">Maguindanao del Sur</option>
                                                    <option value="Sulu">Sulu</option>
                                                    <option value="Tawi-Tawi">Tawi-Tawi</option>
                                                </optgroup>
                                            </select>
                                        </div>

                                        <!-- Municipality Dropdown -->
                                        <div class="mb-4 location-field-wrapper disabled-field" id="municipality-wrapper">
                                            <label for="municipality_select" class="form-label text-dark fw-semibold">
                                                <span class="step-indicator">2</span>
                                                <i class="bx bx-buildings text-primary me-1"></i>Munisipalidad / Lungsod <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select form-select-lg" id="municipality_select" disabled>
                                                <option value="">-- Pumili Muna ng Probinsya --</option>
                                            </select>
                                        </div>

                                        <div class="alert alert-light border text-center mt-4">
                                            <i class="bx bx-info-circle text-primary me-1"></i>
                                            <small class="text-secondary">Nakakatulong ang lokasyon para ma-recommend ang varieties na angkop sa klima at lupa sa lugar mo</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 6: Cropping Season -->
                    <div class="wizard-step d-none" id="step-6">
                        <div class="step-6-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2" id="step6-title">Anong Cropping Season?</h4>
                                <p class="text-secondary" id="step6-subtitle">Piliin ang season para sa planting schedule na ito</p>
                            </div>
                            <input type="hidden" name="cropping_season" id="cropping_season" value="">

                            <div class="season-selection-container">
                                <div class="row justify-content-center g-4">
                                    <!-- Wet Season -->
                                    <div class="col-md-4 col-lg-4">
                                        <div class="season-selection-box" data-season="wet">
                                            <div class="season-icon">
                                                <img class="season-crop-img"
                                                     data-rice-src="{{ asset('images/recommendations/crop-methods/rice-wet-season.webp') }}"
                                                     data-corn-src="{{ asset('images/recommendations/crop-methods/corn-wet-season.webp') }}"
                                                     src="{{ asset('images/recommendations/crop-methods/rice-wet-season.webp') }}"
                                                     alt="Wet Season">
                                            </div>
                                            <h5 class="season-title">Wet Season</h5>
                                            <p class="season-subtitle">Tag-ulan</p>
                                            <p class="season-months">Hunyo - Nobyembre</p>
                                            <div class="season-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dry Season -->
                                    <div class="col-md-4 col-lg-4">
                                        <div class="season-selection-box" data-season="dry">
                                            <div class="season-icon">
                                                <img class="season-crop-img"
                                                     data-rice-src="{{ asset('images/recommendations/crop-methods/rice-dry-season.webp') }}"
                                                     data-corn-src="{{ asset('images/recommendations/crop-methods/corn-dry-season.webp') }}"
                                                     src="{{ asset('images/recommendations/crop-methods/rice-dry-season.webp') }}"
                                                     alt="Dry Season">
                                            </div>
                                            <h5 class="season-title">Dry Season</h5>
                                            <p class="season-subtitle">Tag-init</p>
                                            <p class="season-months">Disyembre - Mayo</p>
                                            <div class="season-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Transition Season -->
                                    <div class="col-md-4 col-lg-4">
                                        <div class="season-selection-box" data-season="transition">
                                            <div class="season-icon">
                                                <img class="season-crop-img"
                                                     data-rice-src="{{ asset('images/recommendations/crop-methods/rice-transition-season.webp') }}"
                                                     data-corn-src="{{ asset('images/recommendations/crop-methods/corn-transition-season.webp') }}"
                                                     src="{{ asset('images/recommendations/crop-methods/rice-transition-season.webp') }}"
                                                     alt="Transition Season">
                                            </div>
                                            <h5 class="season-title">Transition</h5>
                                            <p class="season-subtitle">Pagitan ng Season</p>
                                            <p class="season-months">Sa Pagitan ng mga Season</p>
                                            <div class="season-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <small class="text-secondary" id="season-selection-hint">
                                        <i class="bx bx-info-circle me-1"></i>Pindutin ang isang season para piliin ito
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 7: Yield History -->
                    <div class="wizard-step d-none" id="step-7">
                        <div class="step-6-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Madalas Ba ang Mababang Ani?</h4>
                                <p class="text-secondary">May history ka ba ng mababang ani sa mga nakaraang cropping?</p>
                            </div>
                            <input type="hidden" name="has_low_yield_history" id="has_low_yield_history" value="">
                            <input type="hidden" name="low_yield_reasons" id="low_yield_reasons" value="">

                            <div class="yield-history-container">
                                <!-- Yes/No Selection -->
                                <div class="row justify-content-center g-4 mb-4">
                                    <div class="col-5 col-md-3">
                                        <div class="yield-answer-box" data-answer="yes">
                                            <div class="yield-answer-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <circle cx="32" cy="32" r="28" fill="#FFEBEE"/>
                                                    <circle cx="32" cy="32" r="24" fill="#FFCDD2"/>
                                                    <path d="M20 38 Q32 28 44 38" stroke="#E57373" stroke-width="3" fill="none" stroke-linecap="round"/>
                                                    <circle cx="24" cy="26" r="3" fill="#E57373"/>
                                                    <circle cx="40" cy="26" r="3" fill="#E57373"/>
                                                    <path d="M32 48 L28 56 L36 56 Z" fill="#FFCDD2"/>
                                                </svg>
                                            </div>
                                            <h5 class="yield-answer-title">Oo</h5>
                                            <p class="yield-answer-subtitle">Yes</p>
                                            <div class="yield-answer-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-5 col-md-3">
                                        <div class="yield-answer-box" data-answer="no">
                                            <div class="yield-answer-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <circle cx="32" cy="32" r="28" fill="#E8F5E9"/>
                                                    <circle cx="32" cy="32" r="24" fill="#C8E6C9"/>
                                                    <path d="M20 42 Q32 50 44 42" stroke="#66BB6A" stroke-width="3" fill="none" stroke-linecap="round"/>
                                                    <circle cx="24" cy="26" r="3" fill="#66BB6A"/>
                                                    <circle cx="40" cy="26" r="3" fill="#66BB6A"/>
                                                    <path d="M32 48 L28 56 L36 56 Z" fill="#C8E6C9"/>
                                                </svg>
                                            </div>
                                            <h5 class="yield-answer-title">Hindi</h5>
                                            <p class="yield-answer-subtitle">No</p>
                                            <div class="yield-answer-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-5 col-md-3">
                                        <div class="yield-answer-box" data-answer="first_time">
                                            <div class="yield-answer-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <circle cx="32" cy="32" r="28" fill="#E3F2FD"/>
                                                    <circle cx="32" cy="32" r="24" fill="#BBDEFB"/>
                                                    <path d="M20 38 Q32 38 44 38" stroke="#42A5F5" stroke-width="3" fill="none" stroke-linecap="round"/>
                                                    <circle cx="24" cy="26" r="3" fill="#42A5F5"/>
                                                    <circle cx="40" cy="26" r="3" fill="#42A5F5"/>
                                                    <path d="M32 48 L28 56 L36 56 Z" fill="#BBDEFB"/>
                                                </svg>
                                            </div>
                                            <h5 class="yield-answer-title">First Time Ko</h5>
                                            <p class="yield-answer-subtitle">First time planting</p>
                                            <div class="yield-answer-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Low Yield Reasons (shown when Yes is selected) -->
                                <div id="yield-details-section" class="d-none">
                                    <hr class="my-4">

                                    <!-- Low Yield Reasons -->
                                    <div class="text-center mb-3">
                                        <h5 class="text-dark mb-2">Kung Mababa, Bakit sa Tingin Mo Mababa?</h5>
                                        <p class="text-secondary small">Pumili ng lahat ng applicable</p>
                                    </div>

                                    <div class="yield-reasons-container">
                                        <div class="row justify-content-center g-3">
                                            <!-- Kulang sa Tubig -->
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <div class="reason-box" data-reason="kulang_tubig">
                                                    <div class="reason-icon">
                                                        <img src="{{ asset('images/recommendations/crop-methods/kulang-sa-tubig.webp') }}" alt="Kulang sa Tubig">
                                                    </div>
                                                    <span class="reason-label">Kulang sa Tubig</span>
                                                    <div class="reason-check"><i class="bx bx-check"></i></div>
                                                </div>
                                            </div>

                                            <!-- Sobra sa Tubig -->
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <div class="reason-box" data-reason="sobra_tubig">
                                                    <div class="reason-icon">
                                                        <img src="{{ asset('images/recommendations/crop-methods/sobra-sa-tubig.webp') }}" alt="Sobra sa Tubig">
                                                    </div>
                                                    <span class="reason-label">Sobra sa Tubig</span>
                                                    <div class="reason-check"><i class="bx bx-check"></i></div>
                                                </div>
                                            </div>

                                            <!-- Kulang Abono -->
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <div class="reason-box" data-reason="kulang_abono">
                                                    <div class="reason-icon">
                                                        <img src="{{ asset('images/recommendations/crop-methods/kulang-sa-abono-b.webp') }}" alt="Kulang sa Abono">
                                                    </div>
                                                    <span class="reason-label">Kulang sa Abono</span>
                                                    <div class="reason-check"><i class="bx bx-check"></i></div>
                                                </div>
                                            </div>

                                            <!-- Peste at Sakit -->
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <div class="reason-box" data-reason="peste_sakit">
                                                    <div class="reason-icon">
                                                        <img src="{{ asset('images/recommendations/crop-methods/peste-at-sakit.webp') }}" alt="Peste at Sakit">
                                                    </div>
                                                    <span class="reason-label">Peste at Sakit</span>
                                                    <div class="reason-check"><i class="bx bx-check"></i></div>
                                                </div>
                                            </div>

                                            <!-- Sobra sa Damo -->
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <div class="reason-box" data-reason="damo">
                                                    <div class="reason-icon">
                                                        <img src="{{ asset('images/recommendations/crop-methods/sobra-sa-damo.webp') }}" alt="Sobra sa Damo">
                                                    </div>
                                                    <span class="reason-label">Sobra sa Damo</span>
                                                    <div class="reason-check"><i class="bx bx-check"></i></div>
                                                </div>
                                            </div>

                                            <!-- Kulang sa Bitamina -->
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <div class="reason-box" data-reason="nutrient_deficiency">
                                                    <div class="reason-icon">
                                                        <img src="{{ asset('images/recommendations/crop-methods/kulang-sa-bitamina.webp') }}" alt="Kulang sa Bitamina">
                                                    </div>
                                                    <span class="reason-label">Kulang sa Bitamina</span>
                                                    <div class="reason-check"><i class="bx bx-check"></i></div>
                                                </div>
                                            </div>

                                            <!-- Lodging -->
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <div class="reason-box" data-reason="lodging">
                                                    <div class="reason-icon">
                                                        <img src="{{ asset('images/recommendations/crop-methods/lodging.webp') }}" alt="Pagtumba">
                                                    </div>
                                                    <span class="reason-label">Pagtumba</span>
                                                    <div class="reason-check"><i class="bx bx-check"></i></div>
                                                </div>
                                            </div>

                                            <!-- Pangit na Binhi -->
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <div class="reason-box" data-reason="pangit_binhi">
                                                    <div class="reason-icon">
                                                        <img src="{{ asset('images/recommendations/crop-methods/pangit-na-binhi.webp') }}" alt="Pangit na Binhi">
                                                    </div>
                                                    <span class="reason-label">Pangit na Binhi</span>
                                                    <div class="reason-check"><i class="bx bx-check"></i></div>
                                                </div>
                                            </div>

                                            <!-- Problema sa Lupa -->
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <div class="reason-box" data-reason="soil_problem">
                                                    <div class="reason-icon">
                                                        <img src="{{ asset('images/recommendations/crop-methods/problema-sa-lupa-b.webp') }}" alt="Problema sa Lupa">
                                                    </div>
                                                    <span class="reason-label">Problema sa Lupa</span>
                                                    <div class="reason-check"><i class="bx bx-check"></i></div>
                                                </div>
                                            </div>

                                            <!-- Di Ko Alam -->
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <div class="reason-box" data-reason="unknown">
                                                    <div class="reason-icon">
                                                        <img src="{{ asset('images/recommendations/crop-methods/di-ko-alam.webp') }}" alt="Di Ko Alam">
                                                    </div>
                                                    <span class="reason-label">Di Ko Alam</span>
                                                    <div class="reason-check"><i class="bx bx-check"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <small class="text-secondary" id="yield-history-hint">
                                        <i class="bx bx-info-circle me-1"></i>Pumili kung nakaranas ka na ba ng mababang ani dati
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 8: Soil Type -->
                    <div class="wizard-step d-none" id="step-8">
                        <div class="step-7-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Ano ang Uri ng Lupa sa Iyong Bukid?</h4>
                                <p class="text-secondary">What is the soil type in your farm?</p>
                            </div>
                            <input type="hidden" name="soil_type" id="soil_type" value="">

                            <div class="soil-selection-container">
                                <div class="row justify-content-center g-3">
                                    <!-- Sandy Soil (Mabuhangin) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-selection-box" data-soil="sandy">
                                            <div class="soil-icon">
                                                <img src="{{ asset('images/recommendations/sandy-soil.webp') }}" alt="Sandy Soil" width="72" height="72" style="object-fit: cover; border-radius: 8px;">
                                            </div>
                                            <h6 class="soil-title">Mabuhangin</h6>
                                            <p class="soil-subtitle">Sandy Soil</p>
                                            <div class="soil-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Loamy Soil (Buhaghag na Lupa) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-selection-box" data-soil="loamy">
                                            <div class="soil-icon">
                                                <img src="{{ asset('images/recommendations/loamy-soil.webp') }}" alt="Loamy Soil" width="72" height="72" style="object-fit: cover; border-radius: 8px;">
                                            </div>
                                            <h6 class="soil-title">Buhaghag na Lupa</h6>
                                            <p class="soil-subtitle">Loamy Soil</p>
                                            <div class="soil-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Clay Loose (Clay na Buhaghag) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-selection-box" data-soil="clay_loose">
                                            <div class="soil-icon">
                                                <img src="{{ asset('images/recommendations/loose-clay-soil.webp') }}" alt="Loose Clay Soil" width="72" height="72" style="object-fit: cover; border-radius: 8px;">
                                            </div>
                                            <h6 class="soil-title">Clay na Buhaghag</h6>
                                            <p class="soil-subtitle">Loose Clay</p>
                                            <div class="soil-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Clay Sticky (Clay na Malapot) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-selection-box" data-soil="clay_sticky">
                                            <div class="soil-icon">
                                                <img src="{{ asset('images/recommendations/sticky-clay-soil.webp') }}" alt="Sticky Clay Soil" width="72" height="72" style="object-fit: cover; border-radius: 8px;">
                                            </div>
                                            <h6 class="soil-title">Clay na Malapot</h6>
                                            <p class="soil-subtitle">Sticky Clay</p>
                                            <div class="soil-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Rocky Soil (Mabato) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-selection-box" data-soil="rocky">
                                            <div class="soil-icon">
                                                <img src="{{ asset('images/recommendations/rocky-soil.webp') }}" alt="Rocky Soil" width="72" height="72" style="object-fit: cover; border-radius: 8px;">
                                            </div>
                                            <h6 class="soil-title">Mabato</h6>
                                            <p class="soil-subtitle">Rocky Soil</p>
                                            <div class="soil-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Silty Soil (Pinong Malapulbos) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-selection-box" data-soil="silty">
                                            <div class="soil-icon">
                                                <img src="{{ asset('images/recommendations/silty-soil.webp') }}" alt="Silty Soil" width="72" height="72" style="object-fit: cover; border-radius: 8px;">
                                            </div>
                                            <h6 class="soil-title">Pinong Malapulbos</h6>
                                            <p class="soil-subtitle">Silty Soil</p>
                                            <div class="soil-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Waterlogged (Laging Basa) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-selection-box" data-soil="waterlogged">
                                            <div class="soil-icon">
                                                <img src="{{ asset('images/recommendations/waterlogged-soil.webp') }}" alt="Waterlogged Soil" width="72" height="72" style="object-fit: cover; border-radius: 8px;">
                                            </div>
                                            <h6 class="soil-title">Laging Basa</h6>
                                            <p class="soil-subtitle">Waterlogged</p>
                                            <div class="soil-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Sodic Soil (Mabilis mag Bitak Bitak) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-selection-box" data-soil="sodic">
                                            <div class="soil-icon">
                                                <img src="{{ asset('images/recommendations/sodic-soil.webp') }}" alt="Sodic Soil" width="72" height="72" style="object-fit: cover; border-radius: 8px;">
                                            </div>
                                            <h6 class="soil-title">Mabilis mag Bitak Bitak</h6>
                                            <p class="soil-subtitle">Sodic Soil</p>
                                            <div class="soil-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Di Ko Alam (Don't Know) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-selection-box" data-soil="unknown">
                                            <div class="soil-icon">
                                                <img src="{{ asset('images/recommendations/hindi-ko-alam-soil.webp') }}" alt="I Don't Know" width="72" height="72" style="object-fit: cover; border-radius: 8px;">
                                            </div>
                                            <h6 class="soil-title">Di Ko Alam</h6>
                                            <p class="soil-subtitle">I Don't Know</p>
                                            <div class="soil-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <small class="text-secondary" id="soil-selection-hint">
                                        <i class="bx bx-info-circle me-1"></i>Select the type of soil in your farm
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 9: Personal Yield -->
                    <div class="wizard-step d-none" id="step-9">
                        <div class="step-9-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Ilan ang Kadalasang Inaani Mo?</h4>
                                <p class="text-secondary">Ilan ang inaani mo on average sa <span id="selected-season-display" class="text-primary fw-bold">season</span>?</p>
                            </div>
                            <input type="hidden" name="average_yield" id="average_yield" value="">
                            <input type="hidden" name="yield_unit" id="yield_unit" value="">

                            <div class="row justify-content-center">
                                <div class="col-md-8 col-lg-6">
                                    <!-- Yield Input -->
                                    <div class="d-flex justify-content-center mb-3">
                                        <div class="input-group" style="max-width: 280px;">
                                            <input type="number" class="form-control text-center" id="average_yield_input"
                                                   placeholder="0" step="0.1" min="0" style="font-size: 1.25rem; height: 50px; max-width: 120px;">
                                            <select class="form-select" id="yield_unit_select" style="max-width: 110px; height: 50px;">
                                                <option value="cavans" selected>cav/ha</option>
                                                <option value="tons">ton/ha</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Quick Select -->
                                    <div class="text-center mb-4">
                                        <p class="text-secondary small mb-2">Quick select (cavans per hectare):</p>
                                        <div class="d-flex flex-wrap justify-content-center gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm yield-preset-btn" data-yield="40" data-unit="cavans">40 cav</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm yield-preset-btn" data-yield="60" data-unit="cavans">60 cav</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm yield-preset-btn" data-yield="80" data-unit="cavans">80 cav</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm yield-preset-btn" data-yield="100" data-unit="cavans">100 cav</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm yield-preset-btn" data-yield="120" data-unit="cavans">120 cav</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm yield-preset-btn" data-yield="150" data-unit="cavans">150 cav</button>
                                        </div>
                                    </div>

                                    <div class="alert alert-light border text-center" id="yield-info-note">
                                        <i class="bx bx-info-circle text-primary me-1"></i>
                                        <small class="text-secondary">1 cavan = 50kg. Average Philippine rice yield: 80-100 cavans/hectare</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 10: Neighbor Yield -->
                    <div class="wizard-step d-none" id="step-10">
                        <div class="step-10-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Ilan Inaani ng mga Katabi Mo?</h4>
                                <p class="text-secondary">On average, ilan ang inaani ng mga kapit-bahay mo sa <span id="neighbor-season-display" class="text-primary fw-bold">season</span>?</p>
                            </div>
                            <input type="hidden" name="neighbor_yield" id="neighbor_yield" value="">
                            <input type="hidden" name="neighbor_yield_unit" id="neighbor_yield_unit" value="">

                            <div class="row justify-content-center">
                                <div class="col-md-8 col-lg-6">
                                    <!-- Neighbor Yield Input -->
                                    <div class="d-flex justify-content-center mb-3">
                                        <div class="input-group" style="max-width: 280px;">
                                            <input type="number" class="form-control text-center" id="neighbor_yield_input"
                                                   placeholder="0" step="0.1" min="0" style="font-size: 1.25rem; height: 50px; max-width: 120px;">
                                            <select class="form-select" id="neighbor_yield_unit_select" style="max-width: 110px; height: 50px;">
                                                <option value="cavans" selected>cav/ha</option>
                                                <option value="tons">ton/ha</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Quick Select -->
                                    <div class="text-center mb-4">
                                        <p class="text-secondary small mb-2">Quick select (cavans per hectare):</p>
                                        <div class="d-flex flex-wrap justify-content-center gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm neighbor-yield-preset-btn" data-yield="40" data-unit="cavans">40 cav</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm neighbor-yield-preset-btn" data-yield="60" data-unit="cavans">60 cav</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm neighbor-yield-preset-btn" data-yield="80" data-unit="cavans">80 cav</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm neighbor-yield-preset-btn" data-yield="100" data-unit="cavans">100 cav</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm neighbor-yield-preset-btn" data-yield="120" data-unit="cavans">120 cav</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm neighbor-yield-preset-btn" data-yield="150" data-unit="cavans">150 cav</button>
                                        </div>
                                    </div>

                                    <!-- Skip hint -->
                                    <div class="alert alert-light border text-center">
                                        <i class="bx bx-info-circle text-primary me-1"></i>
                                        <small class="text-secondary">Hindi mo kailangan sagutin ito. Pwede mong i-skip gamit ang "Next" button.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 11: Soil Indicators (Multi-Select) -->
                    <div class="wizard-step d-none" id="step-11">
                        <div class="step-9-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Mga Palatandaan ng Lupa</h4>
                                <p class="text-secondary">Piliin ang mga nakikita mo sa iyong bukid (pwedeng marami)</p>
                            </div>
                            <input type="hidden" name="soil_indicators" id="soil_indicators" value="">

                            <div class="soil-indicators-container">
                                <div class="row justify-content-center g-3">
                                    <!-- White Crust / Alat -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-indicator-box" data-indicator="white_crust">
                                            <button type="button" class="indicator-info-btn" data-bs-toggle="modal" data-bs-target="#whiteCrustModal" onclick="event.stopPropagation();">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                            <div class="indicator-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Soil base -->
                                                    <rect x="4" y="24" width="56" height="36" rx="4" fill="#8D6E63"/>
                                                    <!-- White crust layer on top -->
                                                    <rect x="4" y="24" width="56" height="8" rx="2" fill="#F5F5F5"/>
                                                    <circle cx="12" cy="28" r="2" fill="#E0E0E0"/>
                                                    <circle cx="24" cy="26" r="3" fill="#EEEEEE"/>
                                                    <circle cx="36" cy="28" r="2" fill="#E0E0E0"/>
                                                    <circle cx="48" cy="27" r="2.5" fill="#EEEEEE"/>
                                                    <circle cx="56" cy="28" r="2" fill="#E0E0E0"/>
                                                    <!-- Salt crystals sparkle -->
                                                    <path d="M20 20 L22 16 L24 20 L20 20" fill="#FFF"/>
                                                    <path d="M40 18 L42 14 L44 18 L40 18" fill="#FFF"/>
                                                    <!-- Warning indicator -->
                                                    <circle cx="52" cy="12" r="8" fill="#FF9800"/>
                                                    <text x="52" y="16" text-anchor="middle" font-size="10" font-weight="bold" fill="#FFF">!</text>
                                                </svg>
                                            </div>
                                            <span class="indicator-label">Puti-puting Crust</span>
                                            <span class="indicator-sublabel">(White Crust / Alat)</span>
                                            <div class="indicator-checkbox">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cracks When Dry -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-indicator-box" data-indicator="cracks_dry">
                                            <button type="button" class="indicator-info-btn" data-bs-toggle="modal" data-bs-target="#cracksDryModal" onclick="event.stopPropagation();">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                            <div class="indicator-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Cracked soil -->
                                                    <rect x="4" y="20" width="56" height="40" rx="4" fill="#A1887F"/>
                                                    <!-- Crack patterns -->
                                                    <path d="M12 20 L14 32 L10 44 L14 60" stroke="#5D4037" stroke-width="2" fill="none"/>
                                                    <path d="M28 20 L26 30 L30 40 L26 52 L28 60" stroke="#5D4037" stroke-width="2" fill="none"/>
                                                    <path d="M44 20 L46 28 L42 38 L46 50 L44 60" stroke="#5D4037" stroke-width="2" fill="none"/>
                                                    <path d="M56 20 L54 34 L58 48 L56 60" stroke="#5D4037" stroke-width="2" fill="none"/>
                                                    <!-- Cross cracks -->
                                                    <path d="M10 36 L22 38" stroke="#5D4037" stroke-width="1.5" fill="none"/>
                                                    <path d="M30 44 L42 42" stroke="#5D4037" stroke-width="1.5" fill="none"/>
                                                    <!-- Sun indicating dry -->
                                                    <circle cx="52" cy="10" r="7" fill="#FFC107"/>
                                                    <path d="M52 1 L52 4 M52 16 L52 19 M43 10 L46 10 M58 10 L61 10" stroke="#FFC107" stroke-width="2"/>
                                                </svg>
                                            </div>
                                            <span class="indicator-label">Nagbi-bitak pag Tuyo</span>
                                            <span class="indicator-sublabel">(Cracks When Dry)</span>
                                            <div class="indicator-checkbox">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hardpan -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-indicator-box" data-indicator="hardpan">
                                            <button type="button" class="indicator-info-btn" data-bs-toggle="modal" data-bs-target="#hardpanModal" onclick="event.stopPropagation();">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                            <div class="indicator-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Top soil layer -->
                                                    <rect x="4" y="16" width="56" height="16" rx="2" fill="#8D6E63"/>
                                                    <!-- Hardpan layer (compacted) -->
                                                    <rect x="4" y="32" width="56" height="12" fill="#4E342E"/>
                                                    <path d="M4 32 L60 32" stroke="#3E2723" stroke-width="2"/>
                                                    <path d="M4 38 L60 38" stroke="#3E2723" stroke-width="1"/>
                                                    <path d="M4 44 L60 44" stroke="#3E2723" stroke-width="2"/>
                                                    <!-- Bottom soil -->
                                                    <rect x="4" y="44" width="56" height="16" rx="2" fill="#6D4C41"/>
                                                    <!-- Plant with restricted root -->
                                                    <path d="M32 4 L32 16" stroke="#66BB6A" stroke-width="3"/>
                                                    <path d="M32 8 Q38 4 42 8" fill="#81C784"/>
                                                    <path d="M32 8 Q26 4 22 8" fill="#81C784"/>
                                                    <!-- Root hitting hardpan -->
                                                    <path d="M32 16 L32 32" stroke="#8D6E63" stroke-width="2" stroke-dasharray="2,2"/>
                                                    <circle cx="32" cy="32" r="3" fill="#EF5350"/>
                                                    <text x="32" y="35" text-anchor="middle" font-size="6" fill="#FFF">X</text>
                                                </svg>
                                            </div>
                                            <span class="indicator-label">May Hardpan</span>
                                            <span class="indicator-sublabel">(Matigas na Layer)</span>
                                            <div class="indicator-checkbox">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Standing Water -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-indicator-box" data-indicator="standing_water">
                                            <button type="button" class="indicator-info-btn" data-bs-toggle="modal" data-bs-target="#standingWaterModal" onclick="event.stopPropagation();">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                            <div class="indicator-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Soil -->
                                                    <rect x="4" y="40" width="56" height="20" rx="2" fill="#6D4C41"/>
                                                    <!-- Standing water -->
                                                    <rect x="4" y="28" width="56" height="16" rx="2" fill="#42A5F5" opacity="0.7"/>
                                                    <!-- Water ripples -->
                                                    <ellipse cx="20" cy="34" rx="8" ry="2" fill="#90CAF9" opacity="0.5"/>
                                                    <ellipse cx="44" cy="36" rx="10" ry="2" fill="#90CAF9" opacity="0.5"/>
                                                    <!-- Drowning plant -->
                                                    <path d="M32 28 L32 12" stroke="#8BC34A" stroke-width="3"/>
                                                    <path d="M32 16 Q38 12 42 16" fill="#A5D6A7"/>
                                                    <path d="M32 16 Q26 12 22 16" fill="#A5D6A7"/>
                                                    <!-- Clock indicating time -->
                                                    <circle cx="52" cy="12" r="8" fill="#FFF" stroke="#90A4AE" stroke-width="1"/>
                                                    <path d="M52 8 L52 12 L55 14" stroke="#546E7A" stroke-width="1.5" fill="none"/>
                                                </svg>
                                            </div>
                                            <span class="indicator-label">Naiipon ang Tubig</span>
                                            <span class="indicator-sublabel">(Standing Water)</span>
                                            <div class="indicator-checkbox">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Yellowing Leaves -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-indicator-box" data-indicator="yellowing">
                                            <button type="button" class="indicator-info-btn" data-bs-toggle="modal" data-bs-target="#yellowingModal" onclick="event.stopPropagation();">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                            <div class="indicator-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Plant stem -->
                                                    <path d="M32 60 L32 24" stroke="#7CB342" stroke-width="4"/>
                                                    <!-- Healthy leaves (bottom) -->
                                                    <path d="M32 50 Q42 46 48 52 Q42 54 32 50" fill="#8BC34A"/>
                                                    <path d="M32 50 Q22 46 16 52 Q22 54 32 50" fill="#8BC34A"/>
                                                    <!-- Yellowing leaves (middle) -->
                                                    <path d="M32 40 Q44 36 50 42 Q44 44 32 40" fill="#CDDC39"/>
                                                    <path d="M32 40 Q20 36 14 42 Q20 44 32 40" fill="#CDDC39"/>
                                                    <!-- Very yellow leaves (top) -->
                                                    <path d="M32 30 Q42 26 46 32 Q42 34 32 30" fill="#FFEB3B"/>
                                                    <path d="M32 30 Q22 26 18 32 Q22 34 32 30" fill="#FFEB3B"/>
                                                    <!-- Tip -->
                                                    <path d="M32 24 Q36 18 38 24 Q36 26 32 24" fill="#FFF59D"/>
                                                    <path d="M32 24 Q28 18 26 24 Q28 26 32 24" fill="#FFF59D"/>
                                                </svg>
                                            </div>
                                            <span class="indicator-label">Naninilaw na Dahon</span>
                                            <span class="indicator-sublabel">(Yellowing Leaves)</span>
                                            <div class="indicator-checkbox">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- White Water from Irrigation -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-indicator-box" data-indicator="white_irrigation_water">
                                            <div class="indicator-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Irrigation canal -->
                                                    <rect x="2" y="30" width="20" height="14" rx="2" fill="#78909C"/>
                                                    <rect x="6" y="33" width="12" height="8" rx="1" fill="#B0BEC5"/>
                                                    <!-- Canal opening / pipe -->
                                                    <rect x="20" y="32" width="8" height="10" rx="1" fill="#607D8B"/>
                                                    <rect x="22" y="34" width="4" height="6" rx="1" fill="#90A4AE"/>
                                                    <!-- White/milky water flowing out -->
                                                    <path d="M28 37 Q34 34 40 37 Q46 40 52 37 Q58 34 62 37" stroke="#E0E0E0" stroke-width="6" fill="none" stroke-linecap="round" opacity="0.9"/>
                                                    <path d="M28 37 Q34 34 40 37 Q46 40 52 37 Q58 34 62 37" stroke="#F5F5F5" stroke-width="3" fill="none" stroke-linecap="round"/>
                                                    <!-- White water pool in paddy -->
                                                    <ellipse cx="46" cy="48" rx="16" ry="8" fill="#E8EAF6" opacity="0.8"/>
                                                    <ellipse cx="46" cy="48" rx="12" ry="5" fill="#F5F5F5" opacity="0.6"/>
                                                    <!-- Soil/paddy base -->
                                                    <rect x="28" y="52" width="34" height="10" rx="2" fill="#8D6E63"/>
                                                    <!-- White particles in water -->
                                                    <circle cx="34" cy="36" r="1.5" fill="#FFF" opacity="0.8"/>
                                                    <circle cx="42" cy="39" r="1" fill="#FFF" opacity="0.7"/>
                                                    <circle cx="50" cy="35" r="1.5" fill="#FFF" opacity="0.8"/>
                                                    <circle cx="56" cy="38" r="1" fill="#FFF" opacity="0.7"/>
                                                    <!-- Warning indicator -->
                                                    <circle cx="10" cy="14" r="8" fill="#FF9800"/>
                                                    <text x="10" y="18" text-anchor="middle" font-size="10" font-weight="bold" fill="#FFF">!</text>
                                                </svg>
                                            </div>
                                            <span class="indicator-label">Maputing Tubig sa Patubig</span>
                                            <span class="indicator-sublabel">(White Irrigation Water)</span>
                                            <div class="indicator-checkbox">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- None of the Above -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="soil-indicator-box" data-indicator="none">
                                            <div class="indicator-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Happy soil and plant -->
                                                    <rect x="4" y="36" width="56" height="24" rx="4" fill="#8D6E63"/>
                                                    <!-- Healthy plant -->
                                                    <path d="M32 36 L32 12" stroke="#4CAF50" stroke-width="4"/>
                                                    <path d="M32 20 Q44 14 50 22 Q44 26 32 20" fill="#66BB6A"/>
                                                    <path d="M32 20 Q20 14 14 22 Q20 26 32 20" fill="#66BB6A"/>
                                                    <path d="M32 28 Q42 22 46 30 Q42 32 32 28" fill="#81C784"/>
                                                    <path d="M32 28 Q22 22 18 30 Q22 32 32 28" fill="#81C784"/>
                                                    <!-- Check mark -->
                                                    <circle cx="52" cy="12" r="9" fill="#4CAF50"/>
                                                    <path d="M47 12 L50 15 L57 8" stroke="#FFF" stroke-width="2" fill="none" stroke-linecap="round"/>
                                                </svg>
                                            </div>
                                            <span class="indicator-label">Wala sa mga Ito</span>
                                            <span class="indicator-sublabel">(None of the Above)</span>
                                            <div class="indicator-checkbox">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <small class="text-secondary" id="soil-indicator-hint">
                                        <i class="bx bx-info-circle me-1"></i>Pumili ng mga nakikita mo sa iyong bukid (pwedeng marami ang piliin)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 12: Soil Test Results -->
                    <div class="wizard-step d-none" id="step-12">
                        <div class="step-12-content">
                            <div class="text-center mb-4">
                                <!-- Soil Test SVG Icon -->
                                <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" width="70" height="70" class="mb-3">
                                    <!-- Clipboard -->
                                    <rect x="16" y="10" width="48" height="60" rx="4" fill="#ECEFF1" stroke="#90A4AE" stroke-width="1.5"/>
                                    <rect x="28" y="6" width="24" height="10" rx="3" fill="#78909C"/>
                                    <circle cx="40" cy="11" r="3" fill="#ECEFF1"/>
                                    <!-- Chart lines on clipboard -->
                                    <rect x="24" y="24" width="32" height="3" rx="1" fill="#81C784"/>
                                    <rect x="24" y="31" width="26" height="3" rx="1" fill="#FFB74D"/>
                                    <rect x="24" y="38" width="30" height="3" rx="1" fill="#64B5F6"/>
                                    <rect x="24" y="45" width="20" height="3" rx="1" fill="#CE93D8"/>
                                    <rect x="24" y="52" width="28" height="3" rx="1" fill="#4DB6AC"/>
                                    <!-- pH badge -->
                                    <circle cx="60" cy="58" r="12" fill="#556ee6"/>
                                    <text x="60" y="55" text-anchor="middle" font-size="7" font-weight="bold" fill="#FFF">pH</text>
                                    <text x="60" y="64" text-anchor="middle" font-size="9" font-weight="bold" fill="#FFF">6.5</text>
                                </svg>
                                <h4 class="text-dark mb-2">May Soil Test Ka Ba?</h4>
                                <p class="text-secondary">Kung meron kang soil analysis result, i-encode ang mga values dito</p>
                            </div>

                            <input type="hidden" name="has_soil_test" id="has_soil_test" value="">

                            <!-- Yes/No Selection -->
                            <div class="row justify-content-center g-3 mb-4">
                                <div class="col-5 col-md-3">
                                    <div class="soil-test-answer-box" data-answer="yes">
                                        <div class="mb-2">
                                            <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" width="40" height="40">
                                                <circle cx="24" cy="24" r="20" fill="#E8F5E9" stroke="#4CAF50" stroke-width="2"/>
                                                <path d="M15 24 L21 30 L33 18" stroke="#4CAF50" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </div>
                                        <span class="text-dark fw-semibold">Oo</span>
                                        <small class="text-secondary d-block">(Meron akong result)</small>
                                        <div class="soil-test-check">
                                            <i class="bx bx-check"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-5 col-md-3">
                                    <div class="soil-test-answer-box" data-answer="no">
                                        <div class="mb-2">
                                            <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" width="40" height="40">
                                                <circle cx="24" cy="24" r="20" fill="#FFEBEE" stroke="#EF5350" stroke-width="2"/>
                                                <path d="M16 16 L32 32 M32 16 L16 32" stroke="#EF5350" stroke-width="3" stroke-linecap="round"/>
                                            </svg>
                                        </div>
                                        <span class="text-dark fw-semibold">Wala</span>
                                        <small class="text-secondary d-block">(Walang soil test)</small>
                                        <div class="soil-test-check">
                                            <i class="bx bx-check"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mb-3">
                                <small class="text-secondary" id="soil-test-hint">
                                    <i class="bx bx-info-circle me-1"></i>Pumili kung meron ka bang soil analysis result
                                </small>
                            </div>

                            <!-- Soil Test Encoding Section (hidden until "Oo") -->
                            <div id="soil-test-encoding-section" class="d-none">
                                <hr class="my-3">
                                <div class="text-center mb-3">
                                    <h6 class="text-dark"><i class="bx bx-edit me-1"></i>I-encode ang Soil Test Values</h6>
                                    <small class="text-secondary">Ilagay lang ang mga available na values. Hindi kailangan kumpletuhin lahat.</small>
                                </div>

                                <div class="row justify-content-center">
                                    <div class="col-lg-10">
                                        <!-- Group 1: Basic Soil Properties -->
                                        <div class="card border mb-3">
                                            <div class="card-header bg-light py-2">
                                                <h6 class="mb-0 text-dark"><i class="bx bx-test-tube text-primary me-1"></i>Basic Properties</h6>
                                            </div>
                                            <div class="card-body py-3">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label for="soil_ph" class="form-label text-dark fw-semibold">pH</label>
                                                        <input type="number" class="form-control" id="soil_ph" name="soil_ph" placeholder="e.g. 6.5" step="0.1" min="0" max="14">
                                                        <small class="text-secondary">Ideal: 5.5 - 7.0</small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="soil_ec" class="form-label text-dark fw-semibold">EC / Salinity <small class="text-secondary fw-normal">(if meron)</small></label>
                                                        <input type="number" class="form-control" id="soil_ec" name="soil_ec" placeholder="e.g. 0.5" step="0.01" min="0">
                                                        <small class="text-secondary">dS/m</small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="soil_om" class="form-label text-dark fw-semibold">OM <small class="text-secondary fw-normal">(Organic Matter)</small></label>
                                                        <input type="number" class="form-control" id="soil_om" name="soil_om" placeholder="e.g. 2.5" step="0.01" min="0">
                                                        <small class="text-secondary">%</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Group 2: Primary Macronutrients (N, P, K) -->
                                        <div class="card border mb-3">
                                            <div class="card-header bg-light py-2">
                                                <h6 class="mb-0 text-dark"><i class="bx bx-leaf text-success me-1"></i>Primary Nutrients (N, P, K)</h6>
                                            </div>
                                            <div class="card-body py-3">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label for="soil_n" class="form-label text-dark fw-semibold">N <small class="text-secondary fw-normal">(Nitrogen)</small></label>
                                                        <input type="number" class="form-control" id="soil_n" name="soil_n" placeholder="e.g. 0.15" step="0.001" min="0">
                                                        <small class="text-secondary">%</small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="soil_p" class="form-label text-dark fw-semibold">P <small class="text-secondary fw-normal">(Phosphorus)</small></label>
                                                        <input type="number" class="form-control" id="soil_p" name="soil_p" placeholder="e.g. 12" step="0.01" min="0">
                                                        <small class="text-secondary">ppm</small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="soil_k" class="form-label text-dark fw-semibold">K <small class="text-secondary fw-normal">(Potassium)</small></label>
                                                        <input type="number" class="form-control" id="soil_k" name="soil_k" placeholder="e.g. 150" step="0.01" min="0">
                                                        <small class="text-secondary">ppm</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Group 3: Secondary Macronutrients (Ca, Mg, Na) -->
                                        <div class="card border mb-3">
                                            <div class="card-header bg-light py-2">
                                                <h6 class="mb-0 text-dark"><i class="bx bx-atom text-info me-1"></i>Secondary Nutrients (Ca, Mg, Na)</h6>
                                            </div>
                                            <div class="card-body py-3">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label for="soil_ca" class="form-label text-dark fw-semibold">Ca <small class="text-secondary fw-normal">(Calcium)</small></label>
                                                        <input type="number" class="form-control" id="soil_ca" name="soil_ca" placeholder="e.g. 1200" step="0.01" min="0">
                                                        <small class="text-secondary">ppm</small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="soil_mg" class="form-label text-dark fw-semibold">Mg <small class="text-secondary fw-normal">(Magnesium)</small></label>
                                                        <input type="number" class="form-control" id="soil_mg" name="soil_mg" placeholder="e.g. 200" step="0.01" min="0">
                                                        <small class="text-secondary">ppm</small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="soil_na" class="form-label text-dark fw-semibold">Na <small class="text-secondary fw-normal">(Sodium)</small></label>
                                                        <input type="number" class="form-control" id="soil_na" name="soil_na" placeholder="e.g. 50" step="0.01" min="0">
                                                        <small class="text-secondary">ppm</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Group 4: Micronutrients (Zn, B, Fe, Mn, Cu) -->
                                        <div class="card border mb-3">
                                            <div class="card-header bg-light py-2">
                                                <h6 class="mb-0 text-dark"><i class="bx bx-scatter-chart text-warning me-1"></i>Micronutrients (Zn, B, Fe, Mn, Cu)</h6>
                                            </div>
                                            <div class="card-body py-3">
                                                <div class="row g-3">
                                                    <div class="col-6 col-md">
                                                        <label for="soil_zn" class="form-label text-dark fw-semibold">Zn</label>
                                                        <input type="number" class="form-control" id="soil_zn" name="soil_zn" placeholder="ppm" step="0.01" min="0">
                                                    </div>
                                                    <div class="col-6 col-md">
                                                        <label for="soil_b" class="form-label text-dark fw-semibold">B</label>
                                                        <input type="number" class="form-control" id="soil_b" name="soil_b" placeholder="ppm" step="0.01" min="0">
                                                    </div>
                                                    <div class="col-6 col-md">
                                                        <label for="soil_fe" class="form-label text-dark fw-semibold">Fe</label>
                                                        <input type="number" class="form-control" id="soil_fe" name="soil_fe" placeholder="ppm" step="0.01" min="0">
                                                    </div>
                                                    <div class="col-6 col-md">
                                                        <label for="soil_mn" class="form-label text-dark fw-semibold">Mn</label>
                                                        <input type="number" class="form-control" id="soil_mn" name="soil_mn" placeholder="ppm" step="0.01" min="0">
                                                    </div>
                                                    <div class="col-6 col-md">
                                                        <label for="soil_cu" class="form-label text-dark fw-semibold">Cu</label>
                                                        <input type="number" class="form-control" id="soil_cu" name="soil_cu" placeholder="ppm" step="0.01" min="0">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Group 5: CEC / Texture -->
                                        <div class="card border mb-3">
                                            <div class="card-header bg-light py-2">
                                                <h6 class="mb-0 text-dark"><i class="bx bx-layer text-secondary me-1"></i>CEC / Texture <small class="text-secondary fw-normal">(if available)</small></h6>
                                            </div>
                                            <div class="card-body py-3">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label for="soil_cec" class="form-label text-dark fw-semibold">CEC <small class="text-secondary fw-normal">(Cation Exchange Capacity)</small></label>
                                                        <input type="number" class="form-control" id="soil_cec" name="soil_cec" placeholder="e.g. 15" step="0.01" min="0">
                                                        <small class="text-secondary">cmol/kg (meq/100g)</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="soil_texture_lab" class="form-label text-dark fw-semibold">Texture <small class="text-secondary fw-normal">(Lab Result)</small></label>
                                                        <select class="form-select" id="soil_texture_lab" name="soil_texture_lab">
                                                            <option value="">-- Select --</option>
                                                            <option value="sand">Sand</option>
                                                            <option value="loamy_sand">Loamy Sand</option>
                                                            <option value="sandy_loam">Sandy Loam</option>
                                                            <option value="loam">Loam</option>
                                                            <option value="silt_loam">Silt Loam</option>
                                                            <option value="silt">Silt</option>
                                                            <option value="sandy_clay_loam">Sandy Clay Loam</option>
                                                            <option value="clay_loam">Clay Loam</option>
                                                            <option value="silty_clay_loam">Silty Clay Loam</option>
                                                            <option value="sandy_clay">Sandy Clay</option>
                                                            <option value="silty_clay">Silty Clay</option>
                                                            <option value="clay">Clay</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 13: Drainage -->
                    <div class="wizard-step d-none" id="step-13">
                        <div class="step-10-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Paano ang Drainage ng Lupa?</h4>
                                <p class="text-secondary">How does water drain in your field?</p>
                            </div>
                            <input type="hidden" name="soil_drainage" id="soil_drainage" value="">

                            <div class="drainage-selection-container">
                                <div class="row justify-content-center g-3">
                                    <!-- Fast Drainage -->
                                    <div class="col-md-4">
                                        <div class="drainage-selection-box" data-drainage="fast">
                                            <button type="button" class="drainage-info-btn" data-bs-toggle="modal" data-bs-target="#fastDrainageModal">
                                                <i class="bx bx-question-mark"></i>
                                            </button>
                                            <div class="drainage-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="55" height="55">
                                                    <!-- Soil -->
                                                    <rect x="4" y="32" width="56" height="28" rx="4" fill="#E8D5B7"/>
                                                    <!-- Water drops going down fast -->
                                                    <path d="M20 8 L20 28" stroke="#42A5F5" stroke-width="3" stroke-linecap="round"/>
                                                    <path d="M32 4 L32 28" stroke="#42A5F5" stroke-width="3" stroke-linecap="round"/>
                                                    <path d="M44 8 L44 28" stroke="#42A5F5" stroke-width="3" stroke-linecap="round"/>
                                                    <!-- Water draining through soil -->
                                                    <path d="M20 36 L20 48" stroke="#42A5F5" stroke-width="2" stroke-linecap="round" stroke-dasharray="4,4"/>
                                                    <path d="M32 36 L32 52" stroke="#42A5F5" stroke-width="2" stroke-linecap="round" stroke-dasharray="4,4"/>
                                                    <path d="M44 36 L44 48" stroke="#42A5F5" stroke-width="2" stroke-linecap="round" stroke-dasharray="4,4"/>
                                                    <!-- Speed arrows -->
                                                    <path d="M54 20 L58 24 L54 28" stroke="#4CAF50" stroke-width="2" fill="none"/>
                                                    <path d="M50 20 L54 24 L50 28" stroke="#4CAF50" stroke-width="2" fill="none"/>
                                                </svg>
                                            </div>
                                            <span class="drainage-title">Mabilis Mawala Tubig</span>
                                            <span class="drainage-subtitle">Fast drainage</span>
                                            <div class="drainage-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Moderate Drainage -->
                                    <div class="col-md-4">
                                        <div class="drainage-selection-box" data-drainage="moderate">
                                            <button type="button" class="drainage-info-btn" data-bs-toggle="modal" data-bs-target="#moderateDrainageModal">
                                                <i class="bx bx-question-mark"></i>
                                            </button>
                                            <div class="drainage-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="55" height="55">
                                                    <!-- Soil -->
                                                    <rect x="4" y="32" width="56" height="28" rx="4" fill="#6D4C41"/>
                                                    <!-- Water level moderate -->
                                                    <rect x="4" y="28" width="56" height="8" rx="2" fill="#42A5F5" opacity="0.5"/>
                                                    <!-- Moderate drain -->
                                                    <path d="M24 36 L24 44" stroke="#42A5F5" stroke-width="2" stroke-linecap="round" stroke-dasharray="3,3"/>
                                                    <path d="M40 36 L40 44" stroke="#42A5F5" stroke-width="2" stroke-linecap="round" stroke-dasharray="3,3"/>
                                                    <!-- Balance/OK symbol -->
                                                    <circle cx="32" cy="14" r="10" fill="#4CAF50"/>
                                                    <path d="M27 14 L30 17 L37 10" stroke="#FFF" stroke-width="2" fill="none" stroke-linecap="round"/>
                                                </svg>
                                            </div>
                                            <span class="drainage-title">Sakto Lang</span>
                                            <span class="drainage-subtitle">Moderate drainage</span>
                                            <div class="drainage-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Slow Drainage -->
                                    <div class="col-md-4">
                                        <div class="drainage-selection-box" data-drainage="slow">
                                            <button type="button" class="drainage-info-btn" data-bs-toggle="modal" data-bs-target="#slowDrainageModal">
                                                <i class="bx bx-question-mark"></i>
                                            </button>
                                            <div class="drainage-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="55" height="55">
                                                    <!-- Soil -->
                                                    <rect x="4" y="40" width="56" height="20" rx="4" fill="#5D4037"/>
                                                    <!-- Standing water (high level) -->
                                                    <rect x="4" y="20" width="56" height="24" rx="4 4 0 0" fill="#42A5F5" opacity="0.7"/>
                                                    <!-- Water waves -->
                                                    <path d="M4 30 Q16 26 28 30 Q40 34 52 30 Q58 28 60 30" fill="none" stroke="#64B5F6" stroke-width="2"/>
                                                    <!-- Warning symbol -->
                                                    <circle cx="32" cy="10" r="8" fill="#f1b44c"/>
                                                    <text x="32" y="14" text-anchor="middle" font-size="10" font-weight="bold" fill="#FFF">!</text>
                                                </svg>
                                            </div>
                                            <span class="drainage-title">Mabagal / Laging Basa</span>
                                            <span class="drainage-subtitle">Slow drainage / waterlogged</span>
                                            <div class="drainage-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <small class="text-secondary" id="drainage-selection-hint">
                                        <i class="bx bx-info-circle me-1"></i>Click to select how water drains in your field
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 14: Soil Problems (New - Suspicion Based) -->
                    <div class="wizard-step d-none" id="step-14">
                        <div class="step-11-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">May Hinala Ka Ba sa Lupa?</h4>
                                <p class="text-secondary">Do you suspect any soil problems? (Select all that apply)</p>
                            </div>
                            <input type="hidden" name="soil_problems" id="soil_problems" value="">

                            <div class="soil-suspicion-container">
                                <div class="row justify-content-center g-3">
                                    <!-- Sodic/Alkaline Suspicion -->
                                    <div class="col-6 col-md-3">
                                        <div class="suspicion-box" data-suspicion="sodic_alkaline">
                                            <button type="button" class="suspicion-info-btn" data-bs-toggle="modal" data-bs-target="#sodicSuspicionModal">
                                                <i class="bx bx-question-mark"></i>
                                            </button>
                                            <div class="suspicion-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <rect x="4" y="28" width="56" height="32" rx="4" fill="#E0E0E0"/>
                                                    <rect x="4" y="40" width="56" height="20" rx="0 0 4 4" fill="#BDBDBD"/>
                                                    <!-- White crusty surface -->
                                                    <ellipse cx="16" cy="32" rx="8" ry="3" fill="#FAFAFA"/>
                                                    <ellipse cx="36" cy="34" rx="10" ry="4" fill="#FFF"/>
                                                    <ellipse cx="52" cy="32" rx="6" ry="2" fill="#FAFAFA"/>
                                                    <!-- pH indicator -->
                                                    <circle cx="52" cy="12" r="10" fill="#9C27B0"/>
                                                    <text x="52" y="16" text-anchor="middle" font-size="10" fill="#FFF">pH+</text>
                                                </svg>
                                            </div>
                                            <span class="suspicion-title">Sodic/Alkaline</span>
                                            <span class="suspicion-subtitle">High pH, white crust, poor structure</span>
                                            <div class="suspicion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Acidic Suspicion -->
                                    <div class="col-6 col-md-3">
                                        <div class="suspicion-box" data-suspicion="acidic">
                                            <button type="button" class="suspicion-info-btn" data-bs-toggle="modal" data-bs-target="#acidicSuspicionModal">
                                                <i class="bx bx-question-mark"></i>
                                            </button>
                                            <div class="suspicion-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <rect x="4" y="28" width="56" height="32" rx="4" fill="#8D6E63"/>
                                                    <rect x="4" y="40" width="56" height="20" rx="0 0 4 4" fill="#6D4C41"/>
                                                    <!-- Yellowish/reddish patches -->
                                                    <ellipse cx="18" cy="36" rx="10" ry="4" fill="#FF8A65" opacity="0.6"/>
                                                    <ellipse cx="42" cy="34" rx="8" ry="3" fill="#FFCC80" opacity="0.6"/>
                                                    <!-- Yellowing plant -->
                                                    <path d="M32 8 L32 26" stroke="#FFC107" stroke-width="2"/>
                                                    <path d="M32 12 Q38 8 42 12" fill="#FFEB3B"/>
                                                    <path d="M32 12 Q26 8 22 12" fill="#FFF176"/>
                                                    <!-- pH indicator -->
                                                    <circle cx="52" cy="12" r="8" fill="#FF5722"/>
                                                    <text x="52" y="16" text-anchor="middle" font-size="9" fill="#FFF">pH-</text>
                                                </svg>
                                            </div>
                                            <span class="suspicion-title">Acidic</span>
                                            <span class="suspicion-subtitle">Low pH, yellowing plants</span>
                                            <div class="suspicion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Compaction/Hardpan -->
                                    <div class="col-6 col-md-3">
                                        <div class="suspicion-box" data-suspicion="compaction">
                                            <button type="button" class="suspicion-info-btn" data-bs-toggle="modal" data-bs-target="#compactionSuspicionModal">
                                                <i class="bx bx-question-mark"></i>
                                            </button>
                                            <div class="suspicion-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Compacted soil -->
                                                    <rect x="4" y="28" width="56" height="32" rx="4" fill="#5D4037"/>
                                                    <!-- Hard layer -->
                                                    <rect x="4" y="36" width="56" height="6" fill="#3E2723"/>
                                                    <!-- Water pooling on top -->
                                                    <ellipse cx="32" cy="26" rx="20" ry="4" fill="#42A5F5" opacity="0.6"/>
                                                    <!-- Roots blocked -->
                                                    <path d="M24 28 L24 34" stroke="#8D6E63" stroke-width="2"/>
                                                    <path d="M24 34 L22 36 L26 36 Z" fill="#f46a6a"/>
                                                    <path d="M40 28 L40 34" stroke="#8D6E63" stroke-width="2"/>
                                                    <path d="M40 34 L38 36 L42 36 Z" fill="#f46a6a"/>
                                                    <!-- Warning -->
                                                    <circle cx="52" cy="12" r="8" fill="#f46a6a"/>
                                                    <text x="52" y="16" text-anchor="middle" font-size="10" fill="#FFF">!</text>
                                                </svg>
                                            </div>
                                            <span class="suspicion-title">Compaction/Hardpan</span>
                                            <span class="suspicion-subtitle">Roots blocked, water pools</span>
                                            <div class="suspicion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Low Organic Matter -->
                                    <div class="col-6 col-md-3">
                                        <div class="suspicion-box" data-suspicion="low_organic">
                                            <button type="button" class="suspicion-info-btn" data-bs-toggle="modal" data-bs-target="#lowOrganicModal">
                                                <i class="bx bx-question-mark"></i>
                                            </button>
                                            <div class="suspicion-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Pale/light colored soil -->
                                                    <rect x="4" y="28" width="56" height="32" rx="4" fill="#BCAAA4"/>
                                                    <rect x="4" y="40" width="56" height="20" rx="0 0 4 4" fill="#A1887F"/>
                                                    <!-- Poor structure -->
                                                    <ellipse cx="20" cy="36" rx="6" ry="3" fill="#D7CCC8"/>
                                                    <ellipse cx="44" cy="38" rx="8" ry="3" fill="#D7CCC8"/>
                                                    <!-- Weak/small plant -->
                                                    <path d="M32 14 L32 28" stroke="#9E9E9E" stroke-width="2"/>
                                                    <path d="M32 18 Q36 14 40 18" fill="#BDBDBD"/>
                                                    <path d="M32 18 Q28 14 24 18" fill="#CFD8DC"/>
                                                    <!-- Low indicator -->
                                                    <circle cx="52" cy="12" r="8" fill="#78909C"/>
                                                    <path d="M48 12 L56 12 M52 8 L52 16" stroke="#FFF" stroke-width="2"/>
                                                </svg>
                                            </div>
                                            <span class="suspicion-title">Low Organic Matter</span>
                                            <span class="suspicion-subtitle">Pale soil, poor structure</span>
                                            <div class="suspicion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- No Problems -->
                                    <div class="col-6 col-md-3">
                                        <div class="suspicion-box" data-suspicion="none">
                                            <div class="suspicion-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Healthy soil -->
                                                    <rect x="4" y="28" width="56" height="32" rx="4" fill="#5D4037"/>
                                                    <rect x="4" y="40" width="56" height="20" rx="0 0 4 4" fill="#4E342E"/>
                                                    <!-- Healthy plant -->
                                                    <path d="M32 6 L32 28" stroke="#4CAF50" stroke-width="3"/>
                                                    <path d="M32 10 Q40 4 46 10" fill="#66BB6A"/>
                                                    <path d="M32 10 Q24 4 18 10" fill="#81C784"/>
                                                    <path d="M32 18 Q38 12 44 18" fill="#66BB6A"/>
                                                    <path d="M32 18 Q26 12 20 18" fill="#81C784"/>
                                                    <!-- Checkmark -->
                                                    <circle cx="52" cy="12" r="8" fill="#4CAF50"/>
                                                    <path d="M48 12 L50 14 L56 8" stroke="#FFF" stroke-width="2" fill="none" stroke-linecap="round"/>
                                                </svg>
                                            </div>
                                            <span class="suspicion-title">Walang Hinala</span>
                                            <span class="suspicion-subtitle">No suspected problems</span>
                                            <div class="suspicion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <small class="text-secondary" id="suspicion-selection-hint">
                                        <i class="bx bx-info-circle me-1"></i>Select any suspected soil problems (click <i class="bx bx-question-mark"></i> for more info)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 15: Irrigation Type -->
                    <div class="wizard-step d-none" id="step-15">
                        <div class="step-12-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Irrigation Type</h4>
                                <p class="text-secondary">Paano mo dinidiligang ang sakahan mo?</p>
                            </div>
                            <input type="hidden" name="irrigation_type" id="irrigation_type" value="">

                            <div class="irrigation-container">
                                <div class="row justify-content-center g-3">
                                    <!-- Irrigated (NIA/Canal) -->
                                    <div class="col-6 col-md-3">
                                        <div class="irrigation-box" data-irrigation="nia_canal">
                                            <div class="irrigation-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Canal structure -->
                                                    <rect x="2" y="28" width="60" height="20" rx="2" fill="#78909C"/>
                                                    <rect x="6" y="32" width="52" height="12" fill="#42A5F5"/>
                                                    <!-- Water waves -->
                                                    <path d="M8 38 Q16 34 24 38 Q32 42 40 38 Q48 34 56 38" fill="none" stroke="#90CAF9" stroke-width="2"/>
                                                    <!-- Dam/Gate -->
                                                    <rect x="26" y="20" width="12" height="10" fill="#607D8B"/>
                                                    <rect x="29" y="22" width="6" height="4" fill="#42A5F5"/>
                                                    <!-- NIA label -->
                                                    <rect x="18" y="50" width="28" height="10" rx="2" fill="#1976D2"/>
                                                    <text x="32" y="58" text-anchor="middle" font-size="7" fill="#FFF" font-weight="bold">NIA</text>
                                                </svg>
                                            </div>
                                            <span class="irrigation-label">Irrigated</span>
                                            <span class="irrigation-sublabel">(NIA/Canal)</span>
                                            <div class="irrigation-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Deepwell/Pump -->
                                    <div class="col-6 col-md-3">
                                        <div class="irrigation-box" data-irrigation="deepwell">
                                            <div class="irrigation-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Ground -->
                                                    <rect x="0" y="36" width="64" height="28" fill="#8D6E63"/>
                                                    <!-- Well pipe -->
                                                    <rect x="26" y="8" width="12" height="56" fill="#546E7A"/>
                                                    <rect x="28" y="36" width="8" height="20" fill="#37474F"/>
                                                    <!-- Pump motor -->
                                                    <ellipse cx="32" cy="10" rx="14" ry="8" fill="#607D8B"/>
                                                    <rect x="20" y="6" width="24" height="8" rx="2" fill="#78909C"/>
                                                    <!-- Water spray -->
                                                    <path d="M40 20 Q50 16 52 24" fill="none" stroke="#42A5F5" stroke-width="2"/>
                                                    <path d="M42 22 Q54 20 54 28" fill="none" stroke="#42A5F5" stroke-width="2"/>
                                                    <circle cx="54" cy="26" r="2" fill="#42A5F5"/>
                                                    <circle cx="56" cy="30" r="2" fill="#42A5F5"/>
                                                    <!-- Underground water -->
                                                    <ellipse cx="32" cy="56" rx="10" ry="4" fill="#42A5F5" opacity="0.6"/>
                                                </svg>
                                            </div>
                                            <span class="irrigation-label">Deepwell/Pump</span>
                                            <div class="irrigation-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Rainfed -->
                                    <div class="col-6 col-md-3">
                                        <div class="irrigation-box" data-irrigation="rainfed">
                                            <div class="irrigation-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Cloud -->
                                                    <ellipse cx="32" cy="16" rx="16" ry="10" fill="#90A4AE"/>
                                                    <ellipse cx="20" cy="18" rx="10" ry="8" fill="#90A4AE"/>
                                                    <ellipse cx="44" cy="18" rx="10" ry="8" fill="#90A4AE"/>
                                                    <!-- Rain drops -->
                                                    <path d="M18 28 L18 36" stroke="#42A5F5" stroke-width="2" stroke-linecap="round"/>
                                                    <path d="M26 30 L26 40" stroke="#42A5F5" stroke-width="2" stroke-linecap="round"/>
                                                    <path d="M34 28 L34 38" stroke="#42A5F5" stroke-width="2" stroke-linecap="round"/>
                                                    <path d="M42 32 L42 42" stroke="#42A5F5" stroke-width="2" stroke-linecap="round"/>
                                                    <path d="M50 28 L50 36" stroke="#42A5F5" stroke-width="2" stroke-linecap="round"/>
                                                    <!-- Field -->
                                                    <rect x="4" y="48" width="56" height="12" rx="2" fill="#8D6E63"/>
                                                    <!-- Small plants -->
                                                    <path d="M14 48 L14 44" stroke="#66BB6A" stroke-width="2"/>
                                                    <path d="M28 48 L28 42" stroke="#66BB6A" stroke-width="2"/>
                                                    <path d="M42 48 L42 44" stroke="#66BB6A" stroke-width="2"/>
                                                </svg>
                                            </div>
                                            <span class="irrigation-label">Rainfed</span>
                                            <span class="irrigation-sublabel">(Asa sa Ulan)</span>
                                            <div class="irrigation-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Mixed -->
                                    <div class="col-6 col-md-3">
                                        <div class="irrigation-box" data-irrigation="mixed">
                                            <div class="irrigation-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Split view -->
                                                    <!-- Left side - Rain -->
                                                    <ellipse cx="16" cy="12" rx="10" ry="6" fill="#90A4AE"/>
                                                    <path d="M10 20 L10 28" stroke="#42A5F5" stroke-width="2" stroke-linecap="round"/>
                                                    <path d="M18 22 L18 32" stroke="#42A5F5" stroke-width="2" stroke-linecap="round"/>
                                                    <!-- Right side - Pump -->
                                                    <rect x="44" y="8" width="12" height="6" rx="1" fill="#78909C"/>
                                                    <rect x="48" y="14" width="4" height="20" fill="#546E7A"/>
                                                    <path d="M52 20 Q58 18 58 24" fill="none" stroke="#42A5F5" stroke-width="2"/>
                                                    <!-- Divider -->
                                                    <path d="M32 4 L32 36" stroke="#E0E0E0" stroke-width="1" stroke-dasharray="3,3"/>
                                                    <!-- Field -->
                                                    <rect x="4" y="40" width="56" height="20" rx="2" fill="#8D6E63"/>
                                                    <!-- Plants -->
                                                    <path d="M16 40 L16 34" stroke="#66BB6A" stroke-width="2"/>
                                                    <path d="M32 40 L32 32" stroke="#66BB6A" stroke-width="2"/>
                                                    <path d="M48 40 L48 34" stroke="#66BB6A" stroke-width="2"/>
                                                    <!-- Plus symbol -->
                                                    <circle cx="32" cy="50" r="6" fill="#FFC107"/>
                                                    <path d="M32 47 L32 53 M29 50 L35 50" stroke="#FFF" stroke-width="2"/>
                                                </svg>
                                            </div>
                                            <span class="irrigation-label">Mixed</span>
                                            <div class="irrigation-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <small class="text-secondary" id="irrigation-type-hint">
                                        <i class="bx bx-info-circle me-1"></i>Select your irrigation method(s) - pwedeng marami
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 16: Water Reliability -->
                    <div class="wizard-step d-none" id="step-16">
                        <div class="step-13-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Gaano Ka-Reliable ang Tubig?</h4>
                                <p class="text-secondary">How reliable is your water supply?</p>
                            </div>
                            <input type="hidden" name="water_reliability" id="water_reliability" value="">

                            <div class="irrigation-container">
                                <div class="row justify-content-center g-3">
                                    <!-- Always Available -->
                                    <div class="col-md-4">
                                        <div class="irrigation-box reliability-box" data-reliability="always">
                                            <div class="irrigation-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="55" height="55">
                                                    <!-- Water tank full -->
                                                    <rect x="12" y="8" width="40" height="48" rx="4" fill="#E3F2FD" stroke="#42A5F5" stroke-width="2"/>
                                                    <!-- Water level high -->
                                                    <rect x="14" y="12" width="36" height="42" rx="2" fill="#42A5F5"/>
                                                    <!-- Wave -->
                                                    <path d="M14 18 Q22 14 30 18 Q38 22 46 18 L50 18 L50 54 L14 54 Z" fill="#1976D2"/>
                                                    <!-- Check mark -->
                                                    <circle cx="52" cy="12" r="10" fill="#4CAF50"/>
                                                    <path d="M47 12 L50 15 L57 8" stroke="#FFF" stroke-width="2" fill="none" stroke-linecap="round"/>
                                                </svg>
                                            </div>
                                            <span class="irrigation-label">Palaging May Tubig</span>
                                            <span class="irrigation-sublabel">Always available</span>
                                            <div class="irrigation-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Sometimes Missing -->
                                    <div class="col-md-4">
                                        <div class="irrigation-box reliability-box" data-reliability="sometimes">
                                            <div class="irrigation-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="55" height="55">
                                                    <!-- Water tank half -->
                                                    <rect x="12" y="8" width="40" height="48" rx="4" fill="#E3F2FD" stroke="#FFC107" stroke-width="2"/>
                                                    <!-- Water level medium -->
                                                    <rect x="14" y="32" width="36" height="22" rx="2" fill="#42A5F5"/>
                                                    <path d="M14 36 Q22 32 30 36 Q38 40 46 36 L50 36 L50 54 L14 54 Z" fill="#1976D2"/>
                                                    <!-- Warning mark -->
                                                    <circle cx="52" cy="12" r="10" fill="#FFC107"/>
                                                    <text x="52" y="17" text-anchor="middle" font-size="14" fill="#FFF" font-weight="bold">!</text>
                                                </svg>
                                            </div>
                                            <span class="irrigation-label">Minsan Nawawala</span>
                                            <span class="irrigation-sublabel">Sometimes missing</span>
                                            <div class="irrigation-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Often Lacking -->
                                    <div class="col-md-4">
                                        <div class="irrigation-box reliability-box" data-reliability="often_lacking">
                                            <div class="irrigation-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="55" height="55">
                                                    <!-- Water tank low -->
                                                    <rect x="12" y="8" width="40" height="48" rx="4" fill="#FFEBEE" stroke="#f46a6a" stroke-width="2"/>
                                                    <!-- Water level low -->
                                                    <rect x="14" y="46" width="36" height="8" rx="2" fill="#42A5F5"/>
                                                    <path d="M14 48 Q22 44 30 48 Q38 52 46 48 L50 48 L50 54 L14 54 Z" fill="#1976D2"/>
                                                    <!-- X mark -->
                                                    <circle cx="52" cy="12" r="10" fill="#f46a6a"/>
                                                    <path d="M47 7 L57 17 M57 7 L47 17" stroke="#FFF" stroke-width="2" stroke-linecap="round"/>
                                                </svg>
                                            </div>
                                            <span class="irrigation-label">Madalas Kulang</span>
                                            <span class="irrigation-sublabel">Often lacking</span>
                                            <div class="irrigation-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <small class="text-secondary" id="water-reliability-hint">
                                        <i class="bx bx-info-circle me-1"></i>Select how reliable your water supply is
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 17: Main Goal -->
                    <div class="wizard-step d-none" id="step-17">
                        <div class="step-14-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Ano ang Pangunahing Layunin Mo?</h4>
                                <p class="text-secondary">What is your main goal for this cropping?</p>
                            </div>
                            <input type="hidden" name="main_goal" id="main_goal" value="">

                            <div class="goal-selection-container">
                                <div class="row justify-content-center g-4">
                                    <!-- High Yield Goal -->
                                    <div class="col-md-4 col-lg-4">
                                        <div class="goal-selection-box" data-goal="high_yield">
                                            <div class="goal-icon">
                                                <!-- High Yield SVG - Stack of grain/rice bags with upward arrow -->
                                                <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" width="90" height="90">
                                                    <defs>
                                                        <linearGradient id="bagGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                                            <stop offset="0%" style="stop-color:#C9A86C"/>
                                                            <stop offset="100%" style="stop-color:#8B7355"/>
                                                        </linearGradient>
                                                        <linearGradient id="arrowGrad" x1="0%" y1="100%" x2="0%" y2="0%">
                                                            <stop offset="0%" style="stop-color:#34c38f"/>
                                                            <stop offset="100%" style="stop-color:#1abc9c"/>
                                                        </linearGradient>
                                                    </defs>
                                                    <!-- Bottom bag -->
                                                    <rect x="12" y="48" width="28" height="22" rx="3" fill="url(#bagGrad)"/>
                                                    <rect x="15" y="51" width="22" height="3" fill="#A08060" opacity="0.5"/>
                                                    <!-- Middle bag -->
                                                    <rect x="22" y="32" width="28" height="22" rx="3" fill="url(#bagGrad)"/>
                                                    <rect x="25" y="35" width="22" height="3" fill="#A08060" opacity="0.5"/>
                                                    <!-- Top bag -->
                                                    <rect x="32" y="16" width="28" height="22" rx="3" fill="url(#bagGrad)"/>
                                                    <rect x="35" y="19" width="22" height="3" fill="#A08060" opacity="0.5"/>
                                                    <!-- Upward arrow -->
                                                    <path d="M66 45 L66 15 L60 15 L70 5 L80 15 L74 15 L74 45 Z" fill="url(#arrowGrad)"/>
                                                    <!-- Sparkles -->
                                                    <circle cx="72" cy="28" r="2" fill="#FFD700"/>
                                                    <circle cx="78" cy="38" r="1.5" fill="#FFD700"/>
                                                    <circle cx="64" cy="8" r="1.5" fill="#FFD700"/>
                                                </svg>
                                            </div>
                                            <h5 class="goal-title">Pinaka Mataas na Ani</h5>
                                            <p class="goal-subtitle">Maximum Harvest</p>
                                            <p class="goal-description">Prioritize varieties with highest yield potential</p>
                                            <div class="goal-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Balanced/Moderate Spending Goal (Middle) -->
                                    <div class="col-md-4 col-lg-4">
                                        <div class="goal-selection-box" data-goal="balanced">
                                            <div class="goal-icon">
                                                <!-- Balance Scale SVG - Scales with peso and plant balanced -->
                                                <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" width="90" height="90">
                                                    <defs>
                                                        <linearGradient id="scaleGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                                            <stop offset="0%" style="stop-color:#5C6BC0"/>
                                                            <stop offset="100%" style="stop-color:#3949AB"/>
                                                        </linearGradient>
                                                        <linearGradient id="bowlGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                                            <stop offset="0%" style="stop-color:#7986CB"/>
                                                            <stop offset="100%" style="stop-color:#5C6BC0"/>
                                                        </linearGradient>
                                                    </defs>
                                                    <!-- Center pillar -->
                                                    <rect x="37" y="30" width="6" height="40" fill="url(#scaleGrad)"/>
                                                    <!-- Base -->
                                                    <rect x="25" y="68" width="30" height="6" rx="2" fill="#3949AB"/>
                                                    <!-- Top beam (balanced - horizontal) -->
                                                    <rect x="8" y="26" width="64" height="6" rx="2" fill="url(#scaleGrad)"/>
                                                    <!-- Top ornament -->
                                                    <circle cx="40" cy="22" r="6" fill="#FFD700"/>
                                                    <text x="40" y="25" text-anchor="middle" font-size="8" font-weight="bold" fill="#8B4513">✓</text>
                                                    <!-- Left chain -->
                                                    <line x1="16" y1="32" x2="16" y2="44" stroke="#9FA8DA" stroke-width="2"/>
                                                    <!-- Right chain -->
                                                    <line x1="64" y1="32" x2="64" y2="44" stroke="#9FA8DA" stroke-width="2"/>
                                                    <!-- Left bowl with peso -->
                                                    <path d="M4 48 L12 44 L20 44 L28 48 L26 54 Q16 58 6 54 Z" fill="url(#bowlGrad)"/>
                                                    <text x="16" y="53" text-anchor="middle" font-size="10" font-weight="bold" fill="#FFD700">₱</text>
                                                    <!-- Right bowl with plant -->
                                                    <path d="M52 48 L60 44 L68 44 L76 48 L74 54 Q64 58 54 54 Z" fill="url(#bowlGrad)"/>
                                                    <path d="M64 52 Q68 46 72 50" fill="#4CAF50"/>
                                                    <path d="M64 52 Q60 46 56 50" fill="#66BB6A"/>
                                                    <line x1="64" y1="52" x2="64" y2="56" stroke="#2E7D32" stroke-width="1.5"/>
                                                    <!-- Balance indicator stars -->
                                                    <circle cx="40" cy="12" r="2" fill="#FFC107"/>
                                                </svg>
                                            </div>
                                            <h5 class="goal-title">Sakto Lang</h5>
                                            <p class="goal-subtitle">Kaya Gumastos, Hindi Sobra</p>
                                            <p class="goal-description">Moderate spending for reasonable results</p>
                                            <div class="goal-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cost Effective Goal -->
                                    <div class="col-md-4 col-lg-4">
                                        <div class="goal-selection-box" data-goal="cost_effective">
                                            <div class="goal-icon">
                                                <!-- Cost Effective SVG - Peso sign with plant/leaf -->
                                                <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" width="90" height="90">
                                                    <defs>
                                                        <linearGradient id="coinGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                            <stop offset="0%" style="stop-color:#FFD700"/>
                                                            <stop offset="50%" style="stop-color:#FFC107"/>
                                                            <stop offset="100%" style="stop-color:#FF9800"/>
                                                        </linearGradient>
                                                        <linearGradient id="leafGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                            <stop offset="0%" style="stop-color:#4CAF50"/>
                                                            <stop offset="100%" style="stop-color:#2E7D32"/>
                                                        </linearGradient>
                                                    </defs>
                                                    <!-- Coin circle -->
                                                    <circle cx="40" cy="45" r="28" fill="url(#coinGrad)"/>
                                                    <circle cx="40" cy="45" r="24" fill="none" stroke="#B8860B" stroke-width="2"/>
                                                    <!-- Peso symbol ₱ -->
                                                    <text x="40" y="54" text-anchor="middle" font-size="28" font-weight="bold" fill="#8B4513">₱</text>
                                                    <!-- Leaf/plant growing from coin -->
                                                    <path d="M58 20 Q70 15 65 5 Q55 10 58 20" fill="url(#leafGrad)"/>
                                                    <path d="M58 20 Q48 12 52 2 Q60 8 58 20" fill="url(#leafGrad)"/>
                                                    <line x1="58" y1="20" x2="58" y2="28" stroke="#2E7D32" stroke-width="2"/>
                                                    <!-- Small sparkle -->
                                                    <circle cx="20" cy="35" r="2" fill="#FFF" opacity="0.6"/>
                                                </svg>
                                            </div>
                                            <h5 class="goal-title">Aani Pero Tipid sa Gastos</h5>
                                            <p class="goal-subtitle">Harvest with Savings</p>
                                            <p class="goal-description">Balance yield with affordable seed costs</p>
                                            <div class="goal-check">
                                                <i class="bx bx-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <small class="text-secondary" id="goal-selection-hint">
                                        <i class="bx bx-info-circle me-1"></i>Click to select your main farming goal
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 18: Recommendation Inclusions -->
                    <div class="wizard-step d-none" id="step-18">
                        <div class="step-15-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Ano ang Gusto Mo Isama sa Recommendation?</h4>
                                <p class="text-secondary">What would you like to include in your recommendation?</p>
                            </div>
                            <input type="hidden" name="recommendation_inclusions" id="recommendation_inclusions" value="granular_fertilizer">

                            <div class="inclusion-selection-container">
                                <div class="row justify-content-center g-3">
                                    <!-- Granular Fertilizer (Default, Cannot Remove) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="inclusion-box selected locked" data-inclusion="granular_fertilizer">
                                            <div class="inclusion-lock"><i class="bx bx-lock-alt"></i></div>
                                            <div class="inclusion-icon">
                                                <!-- Fertilizer Bag with NPK -->
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <rect x="12" y="18" width="40" height="38" rx="3" fill="#8B5E3C"/>
                                                    <rect x="12" y="18" width="40" height="8" fill="#6D4C41"/>
                                                    <rect x="18" y="12" width="28" height="8" rx="2" fill="#A1887F"/>
                                                    <rect x="17" y="30" width="30" height="18" rx="2" fill="#FFF9C4"/>
                                                    <text x="32" y="43" text-anchor="middle" font-size="10" font-weight="bold" fill="#33691E">NPK</text>
                                                    <circle cx="22" cy="52" r="2" fill="#FFD54F"/>
                                                    <circle cx="28" cy="54" r="2" fill="#FFD54F"/>
                                                    <circle cx="36" cy="52" r="2" fill="#FFD54F"/>
                                                    <circle cx="42" cy="54" r="2" fill="#FFD54F"/>
                                                </svg>
                                            </div>
                                            <span class="inclusion-label">Granular Fertilizer</span>
                                            <div class="inclusion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Herbicide -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="inclusion-box" data-inclusion="herbicide">
                                            <div class="inclusion-icon">
                                                <!-- Spray bottle with weed and X -->
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <rect x="22" y="24" width="16" height="32" rx="2" fill="#2196F3"/>
                                                    <rect x="24" y="16" width="12" height="10" fill="#1976D2"/>
                                                    <rect x="28" y="10" width="4" height="8" fill="#64B5F6"/>
                                                    <path d="M8 42 Q8 32 16 36 L16 50 Q8 52 8 42" fill="#4CAF50"/>
                                                    <path d="M12 30 Q12 22 16 28" stroke="#66BB6A" stroke-width="2" fill="none"/>
                                                    <line x1="6" y1="36" x2="18" y2="48" stroke="#F44336" stroke-width="3" stroke-linecap="round"/>
                                                    <line x1="18" y1="36" x2="6" y2="48" stroke="#F44336" stroke-width="3" stroke-linecap="round"/>
                                                    <path d="M44 28 Q48 24 52 28" stroke="#90CAF9" stroke-width="2" fill="none"/>
                                                    <path d="M46 32 Q50 28 54 32" stroke="#90CAF9" stroke-width="2" fill="none"/>
                                                </svg>
                                            </div>
                                            <span class="inclusion-label">Herbicide</span>
                                            <div class="inclusion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Pesticide Protection -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="inclusion-box" data-inclusion="pesticide">
                                            <div class="inclusion-icon">
                                                <!-- Bug with shield/ban -->
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <ellipse cx="32" cy="36" rx="14" ry="18" fill="#5D4037"/>
                                                    <ellipse cx="32" cy="20" rx="8" ry="6" fill="#4E342E"/>
                                                    <line x1="18" y1="28" x2="10" y2="22" stroke="#3E2723" stroke-width="2" stroke-linecap="round"/>
                                                    <line x1="46" y1="28" x2="54" y2="22" stroke="#3E2723" stroke-width="2" stroke-linecap="round"/>
                                                    <line x1="16" y1="36" x2="8" y2="36" stroke="#3E2723" stroke-width="2" stroke-linecap="round"/>
                                                    <line x1="48" y1="36" x2="56" y2="36" stroke="#3E2723" stroke-width="2" stroke-linecap="round"/>
                                                    <line x1="18" y1="46" x2="10" y2="52" stroke="#3E2723" stroke-width="2" stroke-linecap="round"/>
                                                    <line x1="46" y1="46" x2="54" y2="52" stroke="#3E2723" stroke-width="2" stroke-linecap="round"/>
                                                    <circle cx="28" cy="18" r="2" fill="#FFF"/>
                                                    <circle cx="36" cy="18" r="2" fill="#FFF"/>
                                                    <circle cx="32" cy="36" r="20" fill="none" stroke="#F44336" stroke-width="4"/>
                                                    <line x1="18" y1="22" x2="46" y2="50" stroke="#F44336" stroke-width="4" stroke-linecap="round"/>
                                                </svg>
                                            </div>
                                            <span class="inclusion-label">Pesticide Protection</span>
                                            <div class="inclusion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Fungicide Protection -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="inclusion-box" data-inclusion="fungicide">
                                            <div class="inclusion-icon">
                                                <!-- Mushroom with shield -->
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <ellipse cx="32" cy="28" rx="22" ry="14" fill="#9C27B0"/>
                                                    <rect x="26" y="28" width="12" height="24" fill="#E1BEE7"/>
                                                    <ellipse cx="32" cy="52" rx="8" ry="3" fill="#CE93D8"/>
                                                    <circle cx="24" cy="24" r="4" fill="#E1BEE7"/>
                                                    <circle cx="38" cy="22" r="3" fill="#E1BEE7"/>
                                                    <circle cx="32" cy="28" r="2" fill="#E1BEE7"/>
                                                    <path d="M48 8 L56 14 L56 28 Q56 38 48 42 Q40 38 40 28 L40 14 Z" fill="#4CAF50"/>
                                                    <path d="M45 20 L48 24 L54 18" stroke="#FFF" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </div>
                                            <span class="inclusion-label">Fungicide Protection</span>
                                            <div class="inclusion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Bacteria Protection -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="inclusion-box" data-inclusion="bacteria">
                                            <div class="inclusion-icon">
                                                <!-- Bacteria cell with shield -->
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <ellipse cx="28" cy="36" rx="16" ry="12" fill="#00BCD4"/>
                                                    <circle cx="22" cy="32" r="3" fill="#B2EBF2"/>
                                                    <circle cx="32" cy="38" r="2" fill="#B2EBF2"/>
                                                    <circle cx="26" cy="40" r="2" fill="#B2EBF2"/>
                                                    <line x1="12" y1="36" x2="6" y2="36" stroke="#00ACC1" stroke-width="2" stroke-linecap="round"/>
                                                    <line x1="14" y1="26" x2="8" y2="20" stroke="#00ACC1" stroke-width="2" stroke-linecap="round"/>
                                                    <line x1="14" y1="46" x2="8" y2="52" stroke="#00ACC1" stroke-width="2" stroke-linecap="round"/>
                                                    <line x1="44" y1="36" x2="50" y2="36" stroke="#00ACC1" stroke-width="2" stroke-linecap="round"/>
                                                    <path d="M48 10 L58 16 L58 32 Q58 44 48 50 Q38 44 38 32 L38 16 Z" fill="#F44336"/>
                                                    <path d="M45 26 L48 30 L55 22" stroke="#FFF" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </div>
                                            <span class="inclusion-label">Bacteria Protection</span>
                                            <div class="inclusion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Foliar Application -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="inclusion-box" data-inclusion="foliar">
                                            <div class="inclusion-icon">
                                                <!-- Spray nozzle on leaf -->
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <path d="M36 56 Q16 48 20 28 Q24 12 44 16 Q56 20 52 40 Q48 54 36 56" fill="#4CAF50"/>
                                                    <path d="M36 54 L36 24" stroke="#81C784" stroke-width="2"/>
                                                    <path d="M36 35 Q28 32 30 24" stroke="#81C784" stroke-width="1.5" fill="none"/>
                                                    <path d="M36 44 Q44 40 42 32" stroke="#81C784" stroke-width="1.5" fill="none"/>
                                                    <rect x="4" y="8" width="8" height="20" rx="2" fill="#1976D2"/>
                                                    <rect x="8" y="4" width="6" height="6" fill="#2196F3"/>
                                                    <circle cx="20" cy="18" r="2" fill="#64B5F6"/>
                                                    <circle cx="26" cy="14" r="2" fill="#64B5F6"/>
                                                    <circle cx="24" cy="22" r="1.5" fill="#64B5F6"/>
                                                    <circle cx="30" cy="18" r="1.5" fill="#64B5F6"/>
                                                    <path d="M12 14 L18 16" stroke="#90CAF9" stroke-width="1" stroke-dasharray="2,2"/>
                                                    <path d="M12 18 L20 20" stroke="#90CAF9" stroke-width="1" stroke-dasharray="2,2"/>
                                                </svg>
                                            </div>
                                            <span class="inclusion-label">Foliar Application</span>
                                            <div class="inclusion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Biostimulants -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="inclusion-box" data-inclusion="biostimulants">
                                            <div class="inclusion-icon">
                                                <!-- Plant with energy/growth boost -->
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <rect x="26" y="44" width="12" height="14" fill="#8D6E63"/>
                                                    <line x1="32" y1="44" x2="32" y2="24" stroke="#4CAF50" stroke-width="4"/>
                                                    <path d="M32 36 Q22 32 24 22 Q28 18 32 24" fill="#66BB6A"/>
                                                    <path d="M32 36 Q42 32 40 22 Q36 18 32 24" fill="#66BB6A"/>
                                                    <path d="M32 28 Q26 24 28 16 Q30 12 32 18" fill="#81C784"/>
                                                    <path d="M32 28 Q38 24 36 16 Q34 12 32 18" fill="#81C784"/>
                                                    <path d="M10 32 L16 20 L14 20 L20 8 L18 8 L24 2 L22 14 L24 14 L18 26 L20 26 Z" fill="#FFC107"/>
                                                    <path d="M54 32 L48 20 L50 20 L44 8 L46 8 L40 2 L42 14 L40 14 L46 26 L44 26 Z" fill="#FFC107"/>
                                                </svg>
                                            </div>
                                            <span class="inclusion-label">Biostimulants</span>
                                            <div class="inclusion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Soil Conditioner -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="inclusion-box" data-inclusion="soil_conditioner">
                                            <div class="inclusion-icon">
                                                <!-- Soil layers with hand/organic matter -->
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <rect x="8" y="36" width="48" height="8" fill="#5D4037"/>
                                                    <rect x="8" y="44" width="48" height="8" fill="#6D4C41"/>
                                                    <rect x="8" y="52" width="48" height="8" fill="#8D6E63"/>
                                                    <circle cx="16" cy="40" r="2" fill="#A1887F"/>
                                                    <circle cx="32" cy="42" r="2" fill="#A1887F"/>
                                                    <circle cx="48" cy="40" r="2" fill="#A1887F"/>
                                                    <circle cx="24" cy="48" r="1.5" fill="#BCAAA4"/>
                                                    <circle cx="40" cy="50" r="1.5" fill="#BCAAA4"/>
                                                    <path d="M20 36 L20 26 Q20 22 24 22" stroke="#4CAF50" stroke-width="2" fill="none"/>
                                                    <circle cx="26" cy="20" r="4" fill="#66BB6A"/>
                                                    <path d="M44 36 L44 28 Q44 24 40 24" stroke="#4CAF50" stroke-width="2" fill="none"/>
                                                    <circle cx="38" cy="22" r="3" fill="#81C784"/>
                                                    <path d="M32 36 L32 18" stroke="#4CAF50" stroke-width="2"/>
                                                    <path d="M32 24 Q28 20 30 14" fill="#A5D6A7"/>
                                                    <path d="M32 24 Q36 20 34 14" fill="#A5D6A7"/>
                                                </svg>
                                            </div>
                                            <span class="inclusion-label">Soil Conditioner</span>
                                            <div class="inclusion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Root Ecosystem -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="inclusion-box" data-inclusion="root_ecosystem">
                                            <div class="inclusion-icon">
                                                <!-- Roots with mycorrhizae network -->
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <rect x="8" y="28" width="48" height="4" fill="#A1887F"/>
                                                    <line x1="32" y1="8" x2="32" y2="28" stroke="#4CAF50" stroke-width="4"/>
                                                    <path d="M28 14 Q24 10 26 6" fill="#66BB6A"/>
                                                    <path d="M36 14 Q40 10 38 6" fill="#66BB6A"/>
                                                    <path d="M30 20 Q26 16 28 12" fill="#81C784"/>
                                                    <path d="M34 20 Q38 16 36 12" fill="#81C784"/>
                                                    <path d="M32 32 L32 42" stroke="#8D6E63" stroke-width="3"/>
                                                    <path d="M32 36 Q24 40 18 52" stroke="#795548" stroke-width="2" fill="none"/>
                                                    <path d="M32 36 Q40 40 46 52" stroke="#795548" stroke-width="2" fill="none"/>
                                                    <path d="M32 42 Q28 46 24 56" stroke="#6D4C41" stroke-width="1.5" fill="none"/>
                                                    <path d="M32 42 Q36 46 40 56" stroke="#6D4C41" stroke-width="1.5" fill="none"/>
                                                    <path d="M18 52 Q14 56 12 62" stroke="#5D4037" stroke-width="1" fill="none"/>
                                                    <path d="M46 52 Q50 56 52 62" stroke="#5D4037" stroke-width="1" fill="none"/>
                                                    <circle cx="20" cy="44" r="3" fill="#FFC107" opacity="0.8"/>
                                                    <circle cx="44" cy="46" r="3" fill="#FFC107" opacity="0.8"/>
                                                    <circle cx="28" cy="50" r="2" fill="#FFD54F" opacity="0.8"/>
                                                    <circle cx="38" cy="52" r="2" fill="#FFD54F" opacity="0.8"/>
                                                    <circle cx="16" cy="54" r="2" fill="#FFE082" opacity="0.6"/>
                                                    <circle cx="48" cy="56" r="2" fill="#FFE082" opacity="0.6"/>
                                                </svg>
                                            </div>
                                            <span class="inclusion-label">Root Ecosystem</span>
                                            <div class="inclusion-check"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <div class="alert alert-success border-0 d-inline-block px-4 py-2" style="background: linear-gradient(135deg, rgba(52, 195, 143, 0.1) 0%, rgba(52, 195, 143, 0.2) 100%);">
                                        <i class="bx bx-shield-quarter text-success me-2"></i>
                                        <span class="text-dark"><strong>Pro Tip:</strong> The more protections you include, the higher your chances of securing a successful yield!</span>
                                    </div>
                                </div>

                                <div class="text-center mt-3">
                                    <small class="text-secondary" id="inclusion-selection-hint">
                                        <i class="bx bx-info-circle me-1"></i>Click to select multiple items • Granular Fertilizer is always included
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 19: Leaf Symptoms from Past Cropping -->
                    <div class="wizard-step d-none" id="step-19">
                        <div class="step-16-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">May napapansin ka bang symptoms sa mga dahon?</h4>
                                <p class="text-secondary">Have you noticed any leaf symptoms during past cropping? (Pwedeng marami ang piliin)</p>
                            </div>
                            <input type="hidden" name="leaf_symptoms" id="leaf_symptoms" value="">

                            <div class="leaf-symptoms-container">
                                <div class="row justify-content-center g-3">
                                    <!-- Yellowing -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="leaf-symptom-box" data-symptom="yellowing">
                                            <button type="button" class="symptom-info-btn" data-bs-toggle="modal" data-bs-target="#yellowingSymptomModal" onclick="event.stopPropagation();">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                            <div class="symptom-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Leaf with yellowing -->
                                                    <path d="M32 8 Q48 12 52 32 Q52 52 32 58 Q12 52 12 32 Q16 12 32 8Z" fill="#C5E1A5" stroke="#8BC34A" stroke-width="2"/>
                                                    <path d="M32 12 L32 54 M32 24 L20 16 M32 24 L44 16 M32 36 L18 28 M32 36 L46 28 M32 48 L22 42 M32 48 L42 42" stroke="#8BC34A" stroke-width="1.5" fill="none"/>
                                                    <!-- Yellow patches -->
                                                    <circle cx="22" cy="24" r="5" fill="#FFEB3B"/>
                                                    <circle cx="42" cy="28" r="4" fill="#FFF59D"/>
                                                    <circle cx="26" cy="40" r="4" fill="#FFEE58"/>
                                                    <circle cx="40" cy="38" r="3" fill="#FFF59D"/>
                                                </svg>
                                            </div>
                                            <span class="symptom-label">Yellowing</span>
                                            <span class="symptom-sublabel">(Paninilaw ng Dahon)</span>
                                            <div class="symptom-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Striping -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="leaf-symptom-box" data-symptom="striping">
                                            <button type="button" class="symptom-info-btn" data-bs-toggle="modal" data-bs-target="#stripingSymptomModal" onclick="event.stopPropagation();">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                            <div class="symptom-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Leaf with stripes -->
                                                    <path d="M32 8 Q48 12 52 32 Q52 52 32 58 Q12 52 12 32 Q16 12 32 8Z" fill="#8BC34A" stroke="#689F38" stroke-width="2"/>
                                                    <!-- Yellow stripes along veins -->
                                                    <path d="M32 12 L32 54" stroke="#FFEB3B" stroke-width="4" fill="none"/>
                                                    <path d="M32 24 L20 16" stroke="#FFF59D" stroke-width="3" fill="none"/>
                                                    <path d="M32 24 L44 16" stroke="#FFF59D" stroke-width="3" fill="none"/>
                                                    <path d="M32 36 L18 28" stroke="#FFF59D" stroke-width="3" fill="none"/>
                                                    <path d="M32 36 L46 28" stroke="#FFF59D" stroke-width="3" fill="none"/>
                                                </svg>
                                            </div>
                                            <span class="symptom-label">Striping</span>
                                            <span class="symptom-sublabel">(May Guhit-guhit)</span>
                                            <div class="symptom-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Tip Burn -->
                                    <div class="col-6 6 col-md-4 col-lg-3">
                                        <div class="leaf-symptom-box" data-symptom="tip_burn">
                                            <button type="button" class="symptom-info-btn" data-bs-toggle="modal" data-bs-target="#tipBurnSymptomModal" onclick="event.stopPropagation();">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                            <div class="symptom-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Leaf with burned tip -->
                                                    <path d="M32 8 Q48 12 52 32 Q52 52 32 58 Q12 52 12 32 Q16 12 32 8Z" fill="#8BC34A" stroke="#689F38" stroke-width="2"/>
                                                    <path d="M32 12 L32 54" stroke="#689F38" stroke-width="1.5" fill="none"/>
                                                    <!-- Burned brown tip -->
                                                    <path d="M32 8 Q40 10 42 18 Q36 14 32 18 Q28 14 22 18 Q24 10 32 8Z" fill="#8D6E63"/>
                                                    <path d="M32 8 Q36 9 38 14" stroke="#5D4037" stroke-width="1" fill="none"/>
                                                    <path d="M32 8 Q28 9 26 14" stroke="#5D4037" stroke-width="1" fill="none"/>
                                                </svg>
                                            </div>
                                            <span class="symptom-label">Tip Burn</span>
                                            <span class="symptom-sublabel">(Sunog ang Dulo)</span>
                                            <div class="symptom-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Stunting -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="leaf-symptom-box" data-symptom="stunting">
                                            <button type="button" class="symptom-info-btn" data-bs-toggle="modal" data-bs-target="#stuntingSymptomModal" onclick="event.stopPropagation();">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                            <div class="symptom-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Normal plant vs stunted -->
                                                    <!-- Normal plant (faded in background) -->
                                                    <path d="M20 60 L20 24" stroke="#A5D6A7" stroke-width="3" opacity="0.4"/>
                                                    <path d="M20 30 Q28 24 32 30" fill="#C8E6C9" opacity="0.4"/>
                                                    <path d="M20 30 Q12 24 8 30" fill="#C8E6C9" opacity="0.4"/>
                                                    <!-- Stunted plant (smaller, foreground) -->
                                                    <path d="M44 60 L44 44" stroke="#8BC34A" stroke-width="4"/>
                                                    <path d="M44 48 Q50 44 54 48" fill="#8BC34A"/>
                                                    <path d="M44 48 Q38 44 34 48" fill="#8BC34A"/>
                                                    <!-- Height comparison arrow -->
                                                    <path d="M12 18 L12 58 M8 22 L12 18 L16 22" stroke="#F44336" stroke-width="2" fill="none"/>
                                                    <path d="M56 40 L56 58 M52 44 L56 40 L60 44" stroke="#F44336" stroke-width="2" fill="none"/>
                                                </svg>
                                            </div>
                                            <span class="symptom-label">Stunting</span>
                                            <span class="symptom-sublabel">(Bansot/Di Lumalaki)</span>
                                            <div class="symptom-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Purpling -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="leaf-symptom-box" data-symptom="purpling">
                                            <button type="button" class="symptom-info-btn" data-bs-toggle="modal" data-bs-target="#purplingSymptomModal" onclick="event.stopPropagation();">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                            <div class="symptom-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Leaf with purple/reddish color -->
                                                    <path d="M32 8 Q48 12 52 32 Q52 52 32 58 Q12 52 12 32 Q16 12 32 8Z" fill="#8BC34A" stroke="#689F38" stroke-width="2"/>
                                                    <path d="M32 12 L32 54" stroke="#689F38" stroke-width="1.5" fill="none"/>
                                                    <!-- Purple patches -->
                                                    <ellipse cx="22" cy="28" rx="6" ry="8" fill="#9C27B0" opacity="0.7"/>
                                                    <ellipse cx="42" cy="32" rx="5" ry="7" fill="#7B1FA2" opacity="0.7"/>
                                                    <ellipse cx="28" cy="44" rx="5" ry="6" fill="#AB47BC" opacity="0.6"/>
                                                    <!-- P indicator for Phosphorus -->
                                                    <circle cx="52" cy="12" r="8" fill="#9C27B0"/>
                                                    <text x="52" y="16" text-anchor="middle" font-size="10" font-weight="bold" fill="#FFF">P</text>
                                                </svg>
                                            </div>
                                            <span class="symptom-label">Purpling</span>
                                            <span class="symptom-sublabel">(Namu-mulaklak/P-deficient)</span>
                                            <div class="symptom-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Poor Flowering -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="leaf-symptom-box" data-symptom="poor_flowering">
                                            <button type="button" class="symptom-info-btn" data-bs-toggle="modal" data-bs-target="#poorFloweringModal" onclick="event.stopPropagation();">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                            <div class="symptom-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Rice panicle with few grains -->
                                                    <path d="M32 60 L32 20" stroke="#7CB342" stroke-width="3"/>
                                                    <!-- Few grains only (poor flowering) -->
                                                    <ellipse cx="28" cy="18" rx="3" ry="5" fill="#FDD835" transform="rotate(-20 28 18)"/>
                                                    <ellipse cx="36" cy="22" rx="3" ry="5" fill="#FDD835" transform="rotate(20 36 22)"/>
                                                    <ellipse cx="30" cy="28" rx="2" ry="4" fill="#FDD835" transform="rotate(-10 30 28)"/>
                                                    <!-- Empty spaces indicated -->
                                                    <circle cx="40" cy="16" r="3" fill="none" stroke="#BDBDBD" stroke-dasharray="2,2"/>
                                                    <circle cx="24" cy="26" r="3" fill="none" stroke="#BDBDBD" stroke-dasharray="2,2"/>
                                                    <circle cx="38" cy="32" r="3" fill="none" stroke="#BDBDBD" stroke-dasharray="2,2"/>
                                                    <!-- Warning -->
                                                    <circle cx="52" cy="12" r="7" fill="#FF9800"/>
                                                    <text x="52" y="16" text-anchor="middle" font-size="10" font-weight="bold" fill="#FFF">!</text>
                                                </svg>
                                            </div>
                                            <span class="symptom-label">Poor Flowering</span>
                                            <span class="symptom-sublabel">(Konti ang Bulaklak/Pollen)</span>
                                            <div class="symptom-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Poor Grain Fill -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="leaf-symptom-box" data-symptom="poor_grain_fill">
                                            <button type="button" class="symptom-info-btn" data-bs-toggle="modal" data-bs-target="#poorGrainFillModal" onclick="event.stopPropagation();">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                            <div class="symptom-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Rice panicle with unfilled grains -->
                                                    <path d="M32 60 L32 16" stroke="#7CB342" stroke-width="3"/>
                                                    <!-- Mix of filled and unfilled grains -->
                                                    <ellipse cx="26" cy="16" rx="3" ry="5" fill="#FDD835" transform="rotate(-25 26 16)"/>
                                                    <ellipse cx="38" cy="18" rx="3" ry="5" fill="none" stroke="#BDBDBD" stroke-width="1" transform="rotate(25 38 18)"/>
                                                    <ellipse cx="28" cy="26" rx="3" ry="5" fill="none" stroke="#BDBDBD" stroke-width="1" transform="rotate(-15 28 26)"/>
                                                    <ellipse cx="36" cy="28" rx="3" ry="5" fill="#FDD835" transform="rotate(15 36 28)"/>
                                                    <ellipse cx="24" cy="36" rx="2" ry="4" fill="none" stroke="#BDBDBD" stroke-width="1" transform="rotate(-10 24 36)"/>
                                                    <ellipse cx="40" cy="34" rx="2" ry="4" fill="none" stroke="#BDBDBD" stroke-width="1" transform="rotate(10 40 34)"/>
                                                    <!-- "Ipa" label -->
                                                    <text x="52" y="44" font-size="7" fill="#795548" font-weight="bold">Ipa</text>
                                                </svg>
                                            </div>
                                            <span class="symptom-label">Poor Grain Fill</span>
                                            <span class="symptom-sublabel">(Maraming Ipa/Di Napuno)</span>
                                            <div class="symptom-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- None of the Above -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="leaf-symptom-box" data-symptom="none">
                                            <div class="symptom-icon">
                                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
                                                    <!-- Healthy plant -->
                                                    <path d="M32 60 L32 20" stroke="#4CAF50" stroke-width="4"/>
                                                    <path d="M32 28 Q46 20 54 30 Q46 36 32 28" fill="#66BB6A"/>
                                                    <path d="M32 28 Q18 20 10 30 Q18 36 32 28" fill="#66BB6A"/>
                                                    <path d="M32 40 Q44 34 50 42 Q44 46 32 40" fill="#81C784"/>
                                                    <path d="M32 40 Q20 34 14 42 Q20 46 32 40" fill="#81C784"/>
                                                    <!-- Checkmark -->
                                                    <circle cx="52" cy="14" r="9" fill="#4CAF50"/>
                                                    <path d="M47 14 L50 17 L57 10" stroke="#FFF" stroke-width="2" fill="none" stroke-linecap="round"/>
                                                </svg>
                                            </div>
                                            <span class="symptom-label">Wala sa mga Ito</span>
                                            <span class="symptom-sublabel">(Malusog ang Halaman)</span>
                                            <div class="symptom-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <small class="text-secondary" id="leaf-symptom-hint">
                                        <i class="bx bx-info-circle me-1"></i>Pumili ng mga symptoms na napansin mo sa nakaraang cropping (pwedeng marami)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 20: Usual Pests & Diseases in Area -->
                    <div class="wizard-step d-none" id="step-20">
                        <div class="step-18-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Ano ang usual pests at sakit sa inyong area?</h4>
                                <p class="text-secondary">What pests and diseases have you observed? (Pwedeng marami ang piliin, o wala)</p>
                            </div>
                            <input type="hidden" name="usual_pests" id="usual_pests" value="">

                            <!-- Rice Pests & Diseases Section -->
                            <div class="pest-section mb-4" id="rice-pests-section">
                                <h5 class="text-dark mb-3"><i class="mdi mdi-rice me-2"></i>Rice Pests & Diseases (Para sa Palay)</h5>

                                <h6 class="text-secondary mb-2 mt-3"><i class="bx bx-bug me-1"></i>Insects / Peste</h6>
                                <div class="row g-3">
                                    <!-- BPH - Brown Planthopper -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="bph" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#bphPestModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="32" cy="36" rx="10" ry="14" fill="#8D6E63"/><ellipse cx="32" cy="22" rx="6" ry="8" fill="#A1887F"/><ellipse cx="20" cy="34" rx="8" ry="5" fill="#D7CCC8" opacity="0.7" transform="rotate(-20 20 34)"/><ellipse cx="44" cy="34" rx="8" ry="5" fill="#D7CCC8" opacity="0.7" transform="rotate(20 44 34)"/><path d="M26 44 L18 54 M32 48 L32 58 M38 44 L46 54" stroke="#5D4037" stroke-width="2"/><circle cx="28" cy="20" r="2" fill="#3E2723"/><circle cx="36" cy="20" r="2" fill="#3E2723"/></svg></div>
                                            <span class="pest-label">BPH</span>
                                            <span class="pest-sublabel">(Brown Planthopper)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Green Leafhopper -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="green_leafhopper" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#greenLeafhopperModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="32" cy="34" rx="8" ry="14" fill="#7CB342"/><ellipse cx="32" cy="20" rx="5" ry="7" fill="#8BC34A"/><path d="M26 44 L18 54 M32 48 L32 58 M38 44 L46 54" stroke="#558B2F" stroke-width="2"/><ellipse cx="20" cy="32" rx="8" ry="4" fill="#AED581" opacity="0.6" transform="rotate(-15 20 32)"/><ellipse cx="44" cy="32" rx="8" ry="4" fill="#AED581" opacity="0.6" transform="rotate(15 44 32)"/><circle cx="29" cy="18" r="2" fill="#33691E"/><circle cx="35" cy="18" r="2" fill="#33691E"/></svg></div>
                                            <span class="pest-label">Leafhopper</span>
                                            <span class="pest-sublabel">(Green Leafhopper)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Stem Borer -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="stem_borer" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#stemBorerPestModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect x="28" y="10" width="8" height="50" rx="2" fill="#8BC34A"/><ellipse cx="32" cy="35" rx="3" ry="8" fill="#FFF9C4"/><circle cx="32" cy="28" r="2" fill="#795548"/><circle cx="32" cy="40" r="4" fill="#5D4037"/><path d="M32 10 L32 5" stroke="#A1887F" stroke-width="3"/></svg></div>
                                            <span class="pest-label">Stem Borer</span>
                                            <span class="pest-sublabel">(Uod sa Tangkay)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Leaf Folder -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="leaf_folder" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#leafFolderPestModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><path d="M20 55 Q32 20 44 55" fill="#8BC34A" stroke="#689F38" stroke-width="2"/><path d="M25 50 Q32 35 39 50" fill="none" stroke="#689F38" stroke-width="1.5"/><ellipse cx="32" cy="42" rx="4" ry="2" fill="#FFEB3B"/></svg></div>
                                            <span class="pest-label">Leaf Folder</span>
                                            <span class="pest-sublabel">(Tiklop-dahon)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Rice Bug -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="rice_bug" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#riceBugPestModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="32" cy="32" rx="8" ry="14" fill="#4E342E"/><ellipse cx="32" cy="18" rx="5" ry="6" fill="#5D4037"/><path d="M28 14 Q24 8 20 6 M36 14 Q40 8 44 6" stroke="#3E2723" stroke-width="1.5" fill="none"/><path d="M24 26 L14 22 M24 32 L12 32 M24 38 L14 42 M40 26 L50 22 M40 32 L52 32 M40 38 L50 42" stroke="#3E2723" stroke-width="2"/></svg></div>
                                            <span class="pest-label">Rice Bug</span>
                                            <span class="pest-sublabel">(Atangya/Kuto)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Golden Apple Snail (Kuhol) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="kuhol" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#kuholPestModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="32" cy="42" rx="18" ry="12" fill="#FFA726"/><circle cx="32" cy="30" r="14" fill="#FFB74D"/><path d="M28 24 Q32 16 36 24" fill="none" stroke="#E65100" stroke-width="2"/><circle cx="32" cy="30" r="10" fill="#FF9800"/><path d="M32 22 Q36 26 32 30 Q28 34 32 38" stroke="#E65100" stroke-width="1.5" fill="none"/><path d="M20 46 L16 50 M18 48 L14 52" stroke="#FFB74D" stroke-width="2"/><circle cx="24" cy="38" r="1.5" fill="#3E2723"/></svg></div>
                                            <span class="pest-label">Kuhol</span>
                                            <span class="pest-sublabel">(Golden Apple Snail)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Rice Hispa -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="rice_hispa" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#riceHispaModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="32" cy="34" rx="10" ry="12" fill="#1565C0"/><ellipse cx="32" cy="22" rx="6" ry="6" fill="#1976D2"/><path d="M24 28 L18 24 M40 28 L46 24 M24 34 L16 34 M40 34 L48 34 M24 40 L18 44 M40 40 L46 44" stroke="#0D47A1" stroke-width="2"/><line x1="22" y1="30" x2="18" y2="26" stroke="#0D47A1" stroke-width="1"/><line x1="42" y1="30" x2="46" y2="26" stroke="#0D47A1" stroke-width="1"/></svg></div>
                                            <span class="pest-label">Rice Hispa</span>
                                            <span class="pest-sublabel">(Salagubang ng Palay)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Black Bug -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="black_bug" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#blackBugModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="32" cy="34" rx="12" ry="14" fill="#212121"/><ellipse cx="32" cy="20" rx="7" ry="7" fill="#424242"/><path d="M24 28 L16 22 M40 28 L48 22 M22 36 L14 36 M42 36 L50 36 M24 44 L16 50 M40 44 L48 50" stroke="#212121" stroke-width="2"/><circle cx="28" cy="18" r="2" fill="#F44336"/><circle cx="36" cy="18" r="2" fill="#F44336"/></svg></div>
                                            <span class="pest-label">Black Bug</span>
                                            <span class="pest-sublabel">(Itim na Peste)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Whorl Maggot -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="whorl_maggot" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#whorlMaggotModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><path d="M28 50 L28 20 Q28 14 32 14 Q36 14 36 20 L36 50" fill="#8BC34A" stroke="#689F38" stroke-width="2"/><ellipse cx="32" cy="28" rx="2" ry="6" fill="#FFFDE7"/><circle cx="32" cy="24" r="1.5" fill="#5D4037"/><path d="M26 30 L22 28 M38 30 L42 28" stroke="#FFF" stroke-width="1"/></svg></div>
                                            <span class="pest-label">Whorl Maggot</span>
                                            <span class="pest-sublabel">(Uod ng Dahon)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Mole Cricket -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="mole_cricket" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#moleCricketModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect x="0" y="46" width="64" height="18" fill="#8D6E63"/><ellipse cx="32" cy="38" rx="14" ry="10" fill="#795548"/><ellipse cx="20" cy="34" rx="6" ry="5" fill="#8D6E63"/><path d="M14 32 L8 24 M14 34 L6 30" stroke="#5D4037" stroke-width="3"/><circle cx="16" cy="32" r="2" fill="#212121"/><path d="M38 44 L42 52 M44 44 L48 52" stroke="#5D4037" stroke-width="2"/></svg></div>
                                            <span class="pest-label">Mole Cricket</span>
                                            <span class="pest-sublabel">(Kuriat)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Armyworm -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="armyworm_rice" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#armywormRiceModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="32" cy="38" rx="18" ry="7" fill="#33691E"/><path d="M16 38 Q20 34 24 38 Q28 42 32 38 Q36 34 40 38 Q44 42 48 38" stroke="#1B5E20" stroke-width="1.5" fill="none"/><circle cx="14" cy="38" r="4" fill="#2E7D32"/><path d="M14 34 L50 34 M14 42 L50 42" stroke="#558B2F" stroke-width="1.5"/></svg></div>
                                            <span class="pest-label">Armyworm</span>
                                            <span class="pest-sublabel">(Harabas)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Rats (Rice) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="rats_rice" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#ratsPestModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="28" cy="38" rx="14" ry="10" fill="#9E9E9E"/><ellipse cx="16" cy="32" rx="8" ry="7" fill="#BDBDBD"/><ellipse cx="10" cy="26" rx="4" ry="5" fill="#E0E0E0"/><ellipse cx="18" cy="24" rx="4" ry="5" fill="#E0E0E0"/><circle cx="12" cy="32" r="2" fill="#212121"/><circle cx="8" cy="34" r="2" fill="#F48FB1"/><path d="M42 38 Q52 32 58 40" stroke="#9E9E9E" stroke-width="3" fill="none"/></svg></div>
                                            <span class="pest-label">Daga</span>
                                            <span class="pest-sublabel">(Rats)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="text-secondary mb-2 mt-4"><i class="bx bx-virus me-1"></i>Diseases / Sakit ng Palay</h6>
                                <div class="row g-3">
                                    <!-- Rice Blast -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="rice_blast" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#riceBlastModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><path d="M20 58 Q32 10 44 58" fill="#8BC34A" stroke="#689F38" stroke-width="2"/><ellipse cx="28" cy="30" rx="5" ry="3" fill="#795548"/><ellipse cx="36" cy="40" rx="4" ry="2.5" fill="#795548"/><ellipse cx="32" cy="48" rx="3" ry="2" fill="#795548"/><circle cx="52" cy="12" r="8" fill="#F44336"/><text x="52" y="16" text-anchor="middle" font-size="10" fill="#FFF" font-weight="bold">!</text></svg></div>
                                            <span class="pest-label">Rice Blast</span>
                                            <span class="pest-sublabel">(Blas)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Tungro -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="tungro" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#tungroPestModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><path d="M32 58 L32 30" stroke="#C0CA33" stroke-width="4"/><path d="M32 36 Q42 28 48 36" fill="#CDDC39"/><path d="M32 36 Q22 28 16 36" fill="#CDDC39"/><circle cx="40" cy="32" r="4" fill="#FF9800" opacity="0.6"/><circle cx="24" cy="34" r="3" fill="#FF9800" opacity="0.6"/><circle cx="52" cy="14" r="8" fill="#F44336"/><text x="52" y="18" text-anchor="middle" font-size="10" fill="#FFF">V</text></svg></div>
                                            <span class="pest-label">Tungro</span>
                                            <span class="pest-sublabel">(Viral Disease)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Sheath Blight -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="sheath_blight" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#sheathBlightModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect x="28" y="10" width="8" height="48" rx="2" fill="#8BC34A"/><path d="M28 35 Q26 30 28 25" stroke="#A1887F" stroke-width="4"/><path d="M36 40 Q38 35 36 30" stroke="#A1887F" stroke-width="4"/><ellipse cx="28" cy="30" rx="3" ry="5" fill="#BCAAA4" opacity="0.8"/><ellipse cx="36" cy="36" rx="3" ry="5" fill="#BCAAA4" opacity="0.8"/></svg></div>
                                            <span class="pest-label">Sheath Blight</span>
                                            <span class="pest-sublabel">(Sakit ng Katawan)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Brown Spot -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="brown_spot" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#brownSpotModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><path d="M20 58 Q32 10 44 58" fill="#8BC34A" stroke="#689F38" stroke-width="2"/><circle cx="28" cy="28" r="3" fill="#795548"/><circle cx="35" cy="36" r="2.5" fill="#795548"/><circle cx="30" cy="44" r="2" fill="#795548"/><circle cx="38" cy="28" r="2" fill="#795548"/><circle cx="25" cy="38" r="2.5" fill="#795548"/></svg></div>
                                            <span class="pest-label">Brown Spot</span>
                                            <span class="pest-sublabel">(Taling-Puti)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Bacterial Leaf Blight -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="bacterial_leaf_blight" data-crop="rice">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#bacterialBlightModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><path d="M20 58 Q32 10 44 58" fill="#8BC34A" stroke="#689F38" stroke-width="2"/><path d="M32 15 Q38 15 42 20 L42 35 Q38 30 32 30" fill="#FFF9C4" stroke="#F9A825" stroke-width="1"/><path d="M32 15 Q26 15 22 20 L22 35 Q26 30 32 30" fill="#FFF9C4" stroke="#F9A825" stroke-width="1"/></svg></div>
                                            <span class="pest-label">BLB</span>
                                            <span class="pest-sublabel">(Bacterial Leaf Blight)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Corn Pests & Diseases Section -->
                            <div class="pest-section mb-4" id="corn-pests-section">
                                <h5 class="text-dark mb-3"><i class="mdi mdi-corn me-2"></i>Corn Pests & Diseases (Para sa Mais)</h5>

                                <h6 class="text-secondary mb-2 mt-3"><i class="bx bx-bug me-1"></i>Insects / Peste</h6>
                                <div class="row g-3">
                                    <!-- Fall Armyworm -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="fall_armyworm" data-crop="corn">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#fallArmywormModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="32" cy="38" rx="20" ry="8" fill="#558B2F"/><path d="M16 38 Q18 32 20 38 Q22 44 24 38 Q26 32 28 38 Q30 44 32 38 Q34 32 36 38 Q38 44 40 38 Q42 32 44 38 Q46 44 48 38" stroke="#33691E" stroke-width="1.5" fill="none"/><circle cx="12" cy="38" r="5" fill="#4E342E"/><path d="M10 36 L12 38 L14 36 M12 38 L12 42" stroke="#FFF" stroke-width="1"/><path d="M16 34 L48 34 M16 42 L48 42" stroke="#8BC34A" stroke-width="2"/></svg></div>
                                            <span class="pest-label">Fall Armyworm</span>
                                            <span class="pest-sublabel">(Harabas)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Corn Borer -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="corn_borer" data-crop="corn">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#cornBorerModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect x="26" y="8" width="12" height="52" rx="3" fill="#8BC34A"/><path d="M32 20 Q28 28 32 36 Q36 44 32 52" stroke="#5D4037" stroke-width="4" fill="none"/><ellipse cx="32" cy="36" rx="3" ry="6" fill="#FFF9C4"/><circle cx="32" cy="31" r="2" fill="#795548"/><circle cx="32" cy="20" r="3" fill="#3E2723"/></svg></div>
                                            <span class="pest-label">Corn Borer</span>
                                            <span class="pest-sublabel">(Uod ng Mais)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Corn Hopper -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="corn_hopper" data-crop="corn">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#cornHopperModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="32" cy="34" rx="8" ry="12" fill="#A1887F"/><ellipse cx="32" cy="22" rx="5" ry="6" fill="#BCAAA4"/><path d="M26 44 L16 56 M38 44 L48 56" stroke="#795548" stroke-width="2"/><ellipse cx="22" cy="32" rx="7" ry="4" fill="#D7CCC8" opacity="0.6" transform="rotate(-10 22 32)"/><ellipse cx="42" cy="32" rx="7" ry="4" fill="#D7CCC8" opacity="0.6" transform="rotate(10 42 32)"/><circle cx="29" cy="20" r="2" fill="#3E2723"/><circle cx="35" cy="20" r="2" fill="#3E2723"/></svg></div>
                                            <span class="pest-label">Corn Hopper</span>
                                            <span class="pest-sublabel">(Planthopper ng Mais)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Aphids -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="aphids" data-crop="corn">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#aphidsPestModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><path d="M10 50 Q32 20 54 50" fill="#8BC34A" stroke="#689F38" stroke-width="2"/><circle cx="24" cy="40" r="4" fill="#9E9D24"/><circle cx="32" cy="36" r="3" fill="#AFB42B"/><circle cx="40" cy="42" r="4" fill="#9E9D24"/><circle cx="28" cy="44" r="3" fill="#C0CA33"/><circle cx="36" cy="38" r="3" fill="#AFB42B"/></svg></div>
                                            <span class="pest-label">Aphids</span>
                                            <span class="pest-sublabel">(Mga Dapang)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Cutworms -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="cutworms" data-crop="corn">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#cutwormsPestModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect x="0" y="48" width="64" height="16" fill="#8D6E63"/><rect x="20" y="30" width="4" height="18" fill="#8BC34A"/><path d="M22 30 Q28 24 32 30" fill="#A5D6A7"/><path d="M22 30 Q16 24 12 30" fill="#A5D6A7"/><path d="M18 46 L26 46" stroke="#F44336" stroke-width="2"/><ellipse cx="42" cy="52" rx="8" ry="5" fill="#5D4037"/><circle cx="36" cy="52" r="3" fill="#4E342E"/></svg></div>
                                            <span class="pest-label">Cutworms</span>
                                            <span class="pest-sublabel">(Harabas Lupa)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Earworm -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="earworm" data-crop="corn">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#earwormPestModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="32" cy="32" rx="12" ry="22" fill="#FDD835"/><path d="M20 32 Q32 28 44 32 M20 38 Q32 34 44 38 M20 26 Q32 22 44 26" stroke="#F9A825" stroke-width="1.5" fill="none"/><path d="M20 12 Q16 32 20 52" stroke="#8BC34A" stroke-width="4" fill="none"/><path d="M44 12 Q48 32 44 52" stroke="#8BC34A" stroke-width="4" fill="none"/><ellipse cx="32" cy="14" rx="5" ry="4" fill="#558B2F"/></svg></div>
                                            <span class="pest-label">Earworm</span>
                                            <span class="pest-sublabel">(Uod ng Bunga)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Crickets -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="crickets" data-crop="corn">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#cricketsModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="32" cy="36" rx="12" ry="10" fill="#3E2723"/><ellipse cx="22" cy="30" rx="7" ry="6" fill="#4E342E"/><path d="M16 28 Q12 20 8 18 M18 26 Q14 18 10 16" stroke="#3E2723" stroke-width="2" fill="none"/><path d="M38 44 L48 56 L54 52 M26 44 L16 56 L10 52" stroke="#5D4037" stroke-width="2.5" fill="none"/><path d="M40 38 L48 36 M24 38 L16 36" stroke="#3E2723" stroke-width="2"/><circle cx="18" cy="28" r="2" fill="#212121"/></svg></div>
                                            <span class="pest-label">Crickets</span>
                                            <span class="pest-sublabel">(Kuriat/Kuliglig)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Rats (Corn) -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="rats_corn" data-crop="corn">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#ratsPestModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="28" cy="38" rx="14" ry="10" fill="#9E9E9E"/><ellipse cx="16" cy="32" rx="8" ry="7" fill="#BDBDBD"/><ellipse cx="10" cy="26" rx="4" ry="5" fill="#E0E0E0"/><ellipse cx="18" cy="24" rx="4" ry="5" fill="#E0E0E0"/><circle cx="12" cy="32" r="2" fill="#212121"/><circle cx="8" cy="34" r="2" fill="#F48FB1"/><path d="M42 38 Q52 32 58 40" stroke="#9E9E9E" stroke-width="3" fill="none"/></svg></div>
                                            <span class="pest-label">Daga</span>
                                            <span class="pest-sublabel">(Rats)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="text-secondary mb-2 mt-4"><i class="bx bx-virus me-1"></i>Diseases / Sakit ng Mais</h6>
                                <div class="row g-3">
                                    <!-- Downy Mildew -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="downy_mildew" data-crop="corn">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#downyMildewModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><path d="M20 58 Q32 10 44 58" fill="#8BC34A" stroke="#689F38" stroke-width="2"/><path d="M24 20 L40 20 L40 50 L24 50 Z" fill="#E0E0E0" opacity="0.5"/><path d="M26 28 L38 28 M26 36 L38 36 M26 44 L38 44" stroke="#BDBDBD" stroke-width="2"/></svg></div>
                                            <span class="pest-label">Downy Mildew</span>
                                            <span class="pest-sublabel">(Bulok-dahon)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Corn Rust -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="corn_rust" data-crop="corn">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#cornRustModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><path d="M20 58 Q32 10 44 58" fill="#8BC34A" stroke="#689F38" stroke-width="2"/><circle cx="28" cy="28" r="2.5" fill="#E65100"/><circle cx="35" cy="34" r="2" fill="#BF360C"/><circle cx="30" cy="42" r="2.5" fill="#E65100"/><circle cx="38" cy="26" r="2" fill="#BF360C"/><circle cx="25" cy="36" r="2" fill="#E65100"/><circle cx="33" cy="48" r="2" fill="#BF360C"/></svg></div>
                                            <span class="pest-label">Corn Rust</span>
                                            <span class="pest-sublabel">(Kalawang)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Corn Smut -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="corn_smut" data-crop="corn">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#cornSmutModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><ellipse cx="32" cy="34" rx="12" ry="20" fill="#FDD835"/><path d="M20 12 Q16 34 20 52" stroke="#8BC34A" stroke-width="3" fill="none"/><path d="M44 12 Q48 34 44 52" stroke="#8BC34A" stroke-width="3" fill="none"/><circle cx="28" cy="28" r="6" fill="#616161"/><circle cx="36" cy="36" r="5" fill="#424242"/><circle cx="30" cy="42" r="4" fill="#757575"/></svg></div>
                                            <span class="pest-label">Corn Smut</span>
                                            <span class="pest-sublabel">(Uling/Bukol)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>

                                    <!-- Stalk Rot -->
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="pest-box" data-pest="stalk_rot" data-crop="corn">
                                            <button type="button" class="pest-info-btn" data-bs-toggle="modal" data-bs-target="#stalkRotModal" onclick="event.stopPropagation();"><i class="bx bx-info-circle"></i></button>
                                            <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect x="28" y="8" width="8" height="50" rx="2" fill="#8BC34A"/><rect x="28" y="30" width="8" height="20" rx="2" fill="#795548"/><path d="M26 36 L24 32 M38 36 L40 32 M26 42 L24 46 M38 42 L40 46" stroke="#5D4037" stroke-width="1.5"/><text x="32" y="42" text-anchor="middle" font-size="8" fill="#FFF">~</text></svg></div>
                                            <span class="pest-label">Stalk Rot</span>
                                            <span class="pest-sublabel">(Bulok ng Tangkay)</span>
                                            <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- None Option -->
                            <div class="row justify-content-center mt-3">
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="pest-box pest-none-box" data-pest="none">
                                        <div class="pest-icon"><svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect x="4" y="40" width="56" height="20" rx="2" fill="#8D6E63"/><path d="M16 40 L16 24" stroke="#4CAF50" stroke-width="3"/><path d="M16 28 Q22 22 26 28" fill="#66BB6A"/><path d="M16 28 Q10 22 6 28" fill="#66BB6A"/><path d="M32 40 L32 20" stroke="#4CAF50" stroke-width="3"/><path d="M32 26 Q40 18 46 26" fill="#66BB6A"/><path d="M32 26 Q24 18 18 26" fill="#66BB6A"/><path d="M48 40 L48 26" stroke="#4CAF50" stroke-width="3"/><path d="M48 30 Q54 24 58 30" fill="#66BB6A"/><path d="M48 30 Q42 24 38 30" fill="#66BB6A"/><circle cx="54" cy="12" r="8" fill="#4CAF50"/><path d="M49 12 L52 15 L59 8" stroke="#FFF" stroke-width="2" fill="none" stroke-linecap="round"/></svg></div>
                                        <span class="pest-label">Walang Pest</span>
                                        <span class="pest-sublabel">(No Pest Problems)</span>
                                        <div class="pest-checkbox"><i class="bx bx-check"></i></div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <small class="text-secondary">
                                    <i class="bx bx-info-circle me-1"></i>Pumili ng mga pest/sakit na madalas makita sa inyong lugar (pwedeng marami o wala)
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Step 21: Spray Approach -->
                    <div class="wizard-step d-none" id="step-21">
                        <div class="step-19-content">
                            <div class="text-center mb-4">
                                <h4 class="text-dark mb-2">Spray Approach / Paraan ng Pag-spray</h4>
                                <p class="text-secondary">Willing ka ba mag "preventive spray" or threshold-based lang?</p>
                            </div>
                            <input type="hidden" name="spray_approach" id="spray_approach" value="">

                            <div class="row justify-content-center g-4">
                                <!-- Preventive Spray Option -->
                                <div class="col-md-5 col-lg-4">
                                    <div class="spray-approach-box" data-approach="preventive">
                                        <div class="spray-icon">
                                            <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="80" height="80">
                                                <!-- Shield with spray -->
                                                <path d="M32 4 L52 14 V32 C52 48 32 58 32 58 C32 58 12 48 12 32 V14 Z" fill="#34c38f" opacity="0.15" stroke="#34c38f" stroke-width="2"/>
                                                <path d="M32 8 L48 16 V32 C48 45 32 54 32 54 C32 54 16 45 16 32 V16 Z" fill="#34c38f" opacity="0.1"/>
                                                <!-- Spray bottle -->
                                                <rect x="27" y="22" width="10" height="18" rx="2" fill="#34c38f"/>
                                                <rect x="29" y="18" width="6" height="6" rx="1" fill="#2ca67a"/>
                                                <path d="M30 18 L27 12 L37 12 L34 18" fill="#2ca67a"/>
                                                <!-- Spray droplets -->
                                                <circle cx="22" cy="20" r="1.5" fill="#34c38f" opacity="0.7"/>
                                                <circle cx="19" cy="24" r="1.2" fill="#34c38f" opacity="0.5"/>
                                                <circle cx="24" cy="16" r="1" fill="#34c38f" opacity="0.6"/>
                                                <circle cx="42" cy="20" r="1.5" fill="#34c38f" opacity="0.7"/>
                                                <circle cx="45" cy="24" r="1.2" fill="#34c38f" opacity="0.5"/>
                                                <circle cx="40" cy="16" r="1" fill="#34c38f" opacity="0.6"/>
                                                <!-- Checkmark on shield -->
                                                <path d="M26 32 L30 36 L38 28" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </div>
                                        <h5 class="spray-title">Preventive Spray</h5>
                                        <p class="spray-subtitle">Para Malinis at Walang Crop Stress</p>
                                        <small class="spray-desc text-secondary">Regular na pag-spray kahit wala pang nakikitang peste o sakit — mas safe, mas malinis ang taniman</small>
                                        <div class="spray-check"><i class="bx bx-check"></i></div>
                                    </div>
                                </div>

                                <!-- Threshold-based (Symptoms Only) Option -->
                                <div class="col-md-5 col-lg-4">
                                    <div class="spray-approach-box" data-approach="threshold">
                                        <div class="spray-icon">
                                            <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="80" height="80">
                                                <!-- Eye / monitoring symbol -->
                                                <ellipse cx="32" cy="32" rx="22" ry="14" fill="#f1b44c" opacity="0.15" stroke="#f1b44c" stroke-width="2"/>
                                                <circle cx="32" cy="32" r="8" fill="#f1b44c" opacity="0.3" stroke="#f1b44c" stroke-width="1.5"/>
                                                <circle cx="32" cy="32" r="3.5" fill="#f1b44c"/>
                                                <!-- Warning/threshold indicator -->
                                                <path d="M46 12 L50 12 L48 20 Z" fill="#f46a6a" opacity="0.8"/>
                                                <circle cx="48" cy="10" r="2" fill="#f46a6a" opacity="0.8"/>
                                                <!-- Leaf with bug -->
                                                <path d="M14 46 Q20 40 22 48 Q16 50 14 46Z" fill="#4CAF50" opacity="0.6"/>
                                                <circle cx="20" cy="45" r="2" fill="#8D6E63"/>
                                                <line x1="18" y1="44" x2="16" y2="42" stroke="#8D6E63" stroke-width="0.8"/>
                                                <line x1="22" y1="44" x2="24" y2="42" stroke="#8D6E63" stroke-width="0.8"/>
                                                <!-- Peso sign (tipid) -->
                                                <circle cx="50" cy="50" r="7" fill="#34c38f" opacity="0.2" stroke="#34c38f" stroke-width="1.5"/>
                                                <text x="50" y="54" text-anchor="middle" font-size="10" font-weight="bold" fill="#34c38f">₱</text>
                                            </svg>
                                        </div>
                                        <h5 class="spray-title">Kapag May Symptoms</h5>
                                        <p class="spray-subtitle">Threshold-based / Risky Pero Tipid</p>
                                        <small class="spray-desc text-secondary">Mag-spray lang kapag may nakitang peste o sintomas ng sakit — mas matipid pero may risk na kumalat</small>
                                        <div class="spray-check"><i class="bx bx-check"></i></div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <small class="text-secondary" id="spray-selection-hint">
                                    <i class="bx bx-info-circle me-1"></i>Pumili ng paraan ng pag-spray na gusto mo
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wizard Navigation -->
                <div class="wizard-navigation">
                    <button type="button" class="btn btn-secondary" id="prev-btn" style="display: none;">
                        <i class="bx bx-arrow-back me-1"></i>Previous
                    </button>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-primary" id="next-btn">
                            Next<i class="bx bx-arrow-right ms-1"></i>
                        </button>
                        <button type="submit" class="btn btn-success d-none" id="submit-btn">
                            <i class="bx bx-check me-1"></i>Create Recommendation
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== SOIL INFO MODALS ========== -->

    <!-- Sandy Texture Modal -->
    <div class="modal fade soil-info-modal" id="sandyTextureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Mabuhangin (Sandy) Soil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/sandy-soil.jpg') }}"
                             alt="Sandy Soil Texture" class="img-fluid rounded" style="max-height: 150px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Sandy soil - grainy texture</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-0">
                            <li>Grainy texture - parang buhangin sa beach</li>
                            <li>Kapag binasa at piniga, hindi bumuo</li>
                            <li>Mabilis matunaw ang tubig (less than 10 seconds)</li>
                            <li>Light colored - mapusyaw ang kulay</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Characteristics:</strong></p>
                    <ul>
                        <li>Fast drainage - mabilis mawala ang tubig</li>
                        <li>Poor nutrient retention - madaling maubos ang sustansya</li>
                        <li>Easy to work - madaling araruhin</li>
                        <li>Warms up quickly in spring</li>
                    </ul>
                    <div class="info-tip">
                        <h6><i class="bx bx-bulb me-1"></i>Tip:</h6>
                        <p>Sandy soil needs more frequent watering and fertilization. Add organic matter to improve water and nutrient retention.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loam Texture Modal -->
    <div class="modal fade soil-info-modal" id="loamTextureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Loam Soil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/loam-soil.jpg') }}"
                             alt="Loam Soil Texture" class="img-fluid rounded" style="max-height: 150px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Loam soil - dark, crumbly, ideal texture</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-0">
                            <li>Soft, crumbly texture - malambot at nabubuo</li>
                            <li>Dark brown color - maitim na brown</li>
                            <li>Kapag binasa, nabubuo pero hindi sticky</li>
                            <li>Moderate drainage - sakto lang ang pagkasipsip ng tubig</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Characteristics:</strong></p>
                    <ul>
                        <li>Ideal soil type - pinaka-maganda para sa pagtatanim</li>
                        <li>Good balance of sand (40%), silt (40%), clay (20%)</li>
                        <li>Excellent nutrient and water retention</li>
                        <li>Good drainage and aeration</li>
                    </ul>
                    <div class="info-tip">
                        <h6><i class="bx bx-bulb me-1"></i>Tip:</h6>
                        <p>Loam soil is ideal for most crops! Maintain it by adding compost regularly to keep the good structure.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Clay Texture Modal -->
    <div class="modal fade soil-info-modal" id="clayTextureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Malagkit / Clay Soil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/clay-soil.jpg') }}"
                             alt="Clay Soil Texture" class="img-fluid rounded" style="max-height: 150px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Clay soil - sticky, compact texture</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-0">
                            <li>Sticky when wet - malagkit kapag basa</li>
                            <li>Hard and cracks when dry - tumitigas at nagcra-crack pag tuyo</li>
                            <li>Kapag binasa at piniga, parang plasticine</li>
                            <li>Slow to absorb water - matagal mawala ang tubig</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Characteristics:</strong></p>
                    <ul>
                        <li>Slow drainage - matagal mawala ang tubig</li>
                        <li>High nutrient retention - mahusay humawak ng sustansya</li>
                        <li>Difficult to work when wet or dry</li>
                        <li>Prone to compaction</li>
                    </ul>
                    <div class="info-tip">
                        <h6><i class="bx bx-bulb me-1"></i>Tip:</h6>
                        <p>Add gypsum or organic matter to improve clay soil structure. Avoid working the soil when too wet.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Unknown Texture Modal -->
    <div class="modal fade soil-info-modal" id="unknownTextureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Halo-halo / Unknown Soil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Kung Hindi Ka Sure:</h6>
                        <ul class="mb-0">
                            <li>May mix ng iba't-ibang texture</li>
                            <li>Nagbabago-bago ang texture sa iba't-ibang bahagi ng farm</li>
                            <li>Hindi sigurado kung ano ang dominant type</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>How to Test:</strong></p>
                    <ul>
                        <li>Kumuha ng isang kurot na lupa</li>
                        <li>Basain at subukang buuin</li>
                        <li>Sandy = hindi bumubuo, crumbles agad</li>
                        <li>Clay = nagiging smooth ball, sticky</li>
                        <li>Loam = bumubuo pero hindi sticky</li>
                    </ul>
                    <div class="info-tip">
                        <h6><i class="bx bx-bulb me-1"></i>Tip:</h6>
                        <p>If unsure, consider getting a soil test from your local agricultural office for accurate analysis.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- White Crust (pH) Modal -->
    <div class="modal fade soil-info-modal" id="whiteCrustModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Puti-puting Crust / "Alat"</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/salt-affected-soil.jpg') }}"
                             alt="Saline Soil with White Crust" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">White salt crust on soil surface (saline soil)</small>
                    </div>
                    <!-- Visual Illustration -->
                    <div class="text-center mb-3">
                        <svg viewBox="0 0 120 80" xmlns="http://www.w3.org/2000/svg" width="180" height="120">
                            <!-- Soil layers -->
                            <rect x="10" y="35" width="100" height="40" rx="4" fill="#8D6E63"/>
                            <!-- White crust on top -->
                            <rect x="10" y="35" width="100" height="12" rx="3" fill="#F5F5F5"/>
                            <circle cx="22" cy="41" r="3" fill="#E0E0E0"/>
                            <circle cx="38" cy="39" r="4" fill="#EEEEEE"/>
                            <circle cx="55" cy="42" r="3" fill="#E0E0E0"/>
                            <circle cx="72" cy="40" r="3.5" fill="#EEEEEE"/>
                            <circle cx="88" cy="41" r="3" fill="#E0E0E0"/>
                            <circle cx="100" cy="40" r="2.5" fill="#EEEEEE"/>
                            <!-- Salt crystals sparkle -->
                            <path d="M30 30 L32 24 L34 30 L30 30" fill="#FFF" stroke="#E0E0E0" stroke-width="0.5"/>
                            <path d="M60 28 L62 22 L64 28 L60 28" fill="#FFF" stroke="#E0E0E0" stroke-width="0.5"/>
                            <path d="M85 26 L87 20 L89 26 L85 26" fill="#FFF" stroke="#E0E0E0" stroke-width="0.5"/>
                            <!-- Warning -->
                            <circle cx="105" cy="20" r="10" fill="#FF9800"/>
                            <text x="105" y="25" text-anchor="middle" font-size="14" font-weight="bold" fill="#FFF">!</text>
                        </svg>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Ano ang White Crust?</h6>
                        <ul class="mb-3">
                            <li>Puting powder o crust sa ibabaw ng lupa</li>
                            <li>Parang asin na nakakalat (maalat kung tinikman)</li>
                            <li>Lumalabas kapag tuyo ang panahon</li>
                            <li>Madalas makita sa mga mababang lugar</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Ano ang Ibig Sabihin Nito:</strong></p>
                    <ul class="mb-3">
                        <li>Mataas ang asin sa lupa (salinity)</li>
                        <li>Posibleng alkaline ang lupa (pH > 7)</li>
                        <li>Mahina ang drainage kaya nag-iipon ang asin</li>
                        <li>Kailangan ng soil amendment</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-bulb me-1"></i>Payo:</h6>
                        <p class="mb-0">Kung may white crust, ayusin ang drainage at magbuhos ng malinis na tubig. Ang gypsum ay makakatulong sa maalat na lupa.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cracks Dry Modal -->
    <div class="modal fade soil-info-modal" id="cracksDryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Nagbi-bitak pag Tuyo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/cracked-earth.jpg') }}"
                             alt="Cracked Dry Soil" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Cracked dry soil - typical of clay-rich soils</small>
                    </div>
                    <!-- Visual Illustration -->
                    <div class="text-center mb-3">
                        <svg viewBox="0 0 120 80" xmlns="http://www.w3.org/2000/svg" width="180" height="120">
                            <!-- Cracked soil -->
                            <rect x="10" y="25" width="100" height="50" rx="4" fill="#A1887F"/>
                            <!-- Crack patterns -->
                            <path d="M20 25 L23 40 L18 55 L22 75" stroke="#5D4037" stroke-width="2.5" fill="none"/>
                            <path d="M45 25 L42 38 L48 52 L44 65 L46 75" stroke="#5D4037" stroke-width="2.5" fill="none"/>
                            <path d="M70 25 L73 35 L68 48 L72 62 L70 75" stroke="#5D4037" stroke-width="2.5" fill="none"/>
                            <path d="M95 25 L92 42 L98 58 L94 75" stroke="#5D4037" stroke-width="2.5" fill="none"/>
                            <!-- Cross cracks -->
                            <path d="M18 45 L42 48" stroke="#5D4037" stroke-width="1.5" fill="none"/>
                            <path d="M48 55 L68 52" stroke="#5D4037" stroke-width="1.5" fill="none"/>
                            <path d="M73 40 L92 43" stroke="#5D4037" stroke-width="1.5" fill="none"/>
                            <!-- Sun indicating dry -->
                            <circle cx="100" cy="12" r="9" fill="#FFC107"/>
                            <path d="M100 0 L100 4 M100 20 L100 24 M88 12 L92 12 M108 12 L112 12 M92 4 L95 7 M105 19 L108 22 M92 20 L95 17 M105 5 L108 8" stroke="#FFC107" stroke-width="2"/>
                        </svg>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Ano ang Cracking?</h6>
                        <ul class="mb-3">
                            <li>Malalalim na bitak sa lupa pag tuyo</li>
                            <li>Parang puzzle pieces ang lupa (polygons)</li>
                            <li>Nagiging matigas na bato ang lupa</li>
                            <li>Bumabalik sa normal pag nabasa ulit</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Ano ang Ibig Sabihin Nito:</strong></p>
                    <ul class="mb-3">
                        <li>Mataas ang clay content ng lupa</li>
                        <li>Lumulaki ang lupa pag basa, lumiliit pag tuyo</li>
                        <li>Posibleng may problema sa drainage</li>
                        <li>Pwedeng masira ang ugat ng halaman</li>
                    </ul>
                    <div class="info-tip alert alert-info">
                        <h6><i class="bx bx-bulb me-1"></i>Payo:</h6>
                        <p class="mb-0">Dagdagan ng organic matter para mabawasan ang pag-crack. Ang mulching ay makakatulong na mapanatiling consistent ang moisture.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hardpan Modal -->
    <div class="modal fade soil-info-modal" id="hardpanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Hardpan / Matigas na Layer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/hardpan-soil.jpg') }}"
                             alt="Hardpan Layer in Soil" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Compacted hardpan layer - blocks root growth</small>
                    </div>
                    <!-- Visual Illustration -->
                    <div class="text-center mb-3">
                        <svg viewBox="0 0 120 80" xmlns="http://www.w3.org/2000/svg" width="180" height="120">
                            <!-- Top soil layer -->
                            <rect x="10" y="20" width="100" height="18" rx="3" fill="#8D6E63"/>
                            <!-- Hardpan layer (compacted) -->
                            <rect x="10" y="38" width="100" height="14" fill="#4E342E"/>
                            <path d="M10 38 L110 38" stroke="#3E2723" stroke-width="2"/>
                            <path d="M10 45 L110 45" stroke="#3E2723" stroke-width="1"/>
                            <path d="M10 52 L110 52" stroke="#3E2723" stroke-width="2"/>
                            <!-- Bottom soil -->
                            <rect x="10" y="52" width="100" height="18" rx="3" fill="#6D4C41"/>
                            <!-- Plant with restricted root -->
                            <path d="M60 5 L60 20" stroke="#66BB6A" stroke-width="4"/>
                            <path d="M60 10 Q70 4 76 10" fill="#81C784"/>
                            <path d="M60 10 Q50 4 44 10" fill="#81C784"/>
                            <!-- Root hitting hardpan -->
                            <path d="M60 20 L60 38" stroke="#8D6E63" stroke-width="3" stroke-dasharray="3,3"/>
                            <circle cx="60" cy="38" r="5" fill="#EF5350"/>
                            <text x="60" y="42" text-anchor="middle" font-size="8" fill="#FFF" font-weight="bold">X</text>
                            <!-- Label for hardpan -->
                            <text x="115" y="48" font-size="7" fill="#3E2723" text-anchor="end">Hardpan</text>
                        </svg>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Ano ang Hardpan?</h6>
                        <ul class="mb-3">
                            <li>Matigas na layer sa ilalim ng topsoil</li>
                            <li>Parang semento ang tigas, di matusok ng shovel</li>
                            <li>Hindi kayang tumagos ng ugat</li>
                            <li>Nagiging dam - naiipon ang tubig sa itaas</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Ano ang Ibig Sabihin Nito:</strong></p>
                    <ul class="mb-3">
                        <li>Compaction dahil sa mabibigat na equipment</li>
                        <li>Natural na pag-accumulate ng clay</li>
                        <li>Limited ang paglago ng ugat</li>
                        <li>Mahina ang pagtagos ng tubig</li>
                    </ul>
                    <div class="info-tip alert alert-success">
                        <h6><i class="bx bx-bulb me-1"></i>Payo:</h6>
                        <p class="mb-0">Ang deep tillage o subsoiling ay pwedeng mabasag ang hardpan. Magtanim ng deep-rooted cover crops para unti-unting maayos.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Standing Water Modal -->
    <div class="modal fade soil-info-modal" id="standingWaterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Naiipon ang Tubig</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/paddy-field-water.jpg') }}"
                             alt="Standing Water in Paddy Field" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Standing water in rice field - waterlogged conditions</small>
                    </div>
                    <!-- Visual Illustration -->
                    <div class="text-center mb-3">
                        <svg viewBox="0 0 120 80" xmlns="http://www.w3.org/2000/svg" width="180" height="120">
                            <!-- Soil -->
                            <rect x="10" y="50" width="100" height="25" rx="3" fill="#6D4C41"/>
                            <!-- Standing water -->
                            <rect x="10" y="35" width="100" height="20" rx="3" fill="#42A5F5" opacity="0.7"/>
                            <!-- Water ripples -->
                            <ellipse cx="35" cy="42" rx="12" ry="3" fill="#90CAF9" opacity="0.5"/>
                            <ellipse cx="75" cy="45" rx="15" ry="3" fill="#90CAF9" opacity="0.5"/>
                            <!-- Drowning plant -->
                            <path d="M60 35 L60 10" stroke="#8BC34A" stroke-width="4"/>
                            <path d="M60 18 Q72 12 78 18" fill="#A5D6A7"/>
                            <path d="M60 18 Q48 12 42 18" fill="#A5D6A7"/>
                            <!-- Clock indicating time -->
                            <circle cx="100" cy="15" r="10" fill="#FFF" stroke="#90A4AE" stroke-width="1.5"/>
                            <path d="M100 9 L100 15 L104 18" stroke="#546E7A" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Ano ang Standing Water?</h6>
                        <ul class="mb-3">
                            <li>May tubig na naiiwan sa bukid matagal</li>
                            <li>Hindi agad nawawala ang tubig-ulan</li>
                            <li>Laging mabasa ang lupa kahit tuyo na sa iba</li>
                            <li>Mga halaman ay parang "drowning"</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Ano ang Ibig Sabihin Nito:</strong></p>
                    <ul class="mb-3">
                        <li>Poor drainage ng lupa</li>
                        <li>Mataas ang clay content o may hardpan</li>
                        <li>Mababang lugar ng bukid</li>
                        <li>Pwedeng magdulot ng root rot</li>
                    </ul>
                    <div class="info-tip alert alert-primary">
                        <h6><i class="bx bx-bulb me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumawa ng drainage channels. Pumili ng varieties na tolerant sa waterlogging o submergence.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Yellowing Modal -->
    <div class="modal fade soil-info-modal" id="yellowingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Naninilaw na Dahon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/nitrogen-deficiency.jpg') }}"
                             alt="Yellowing Rice Leaves" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Yellowing leaves - often due to nitrogen deficiency</small>
                    </div>
                    <!-- Visual Illustration -->
                    <div class="text-center mb-3">
                        <svg viewBox="0 0 120 80" xmlns="http://www.w3.org/2000/svg" width="180" height="120">
                            <!-- Plant stem -->
                            <path d="M60 75 L60 25" stroke="#7CB342" stroke-width="5"/>
                            <!-- Healthy leaves (bottom) -->
                            <path d="M60 65 Q78 58 88 68 Q78 72 60 65" fill="#8BC34A"/>
                            <path d="M60 65 Q42 58 32 68 Q42 72 60 65" fill="#8BC34A"/>
                            <!-- Yellowing leaves (middle) -->
                            <path d="M60 50 Q80 43 92 53 Q80 57 60 50" fill="#CDDC39"/>
                            <path d="M60 50 Q40 43 28 53 Q40 57 60 50" fill="#CDDC39"/>
                            <!-- Very yellow leaves (top) -->
                            <path d="M60 35 Q76 28 84 38 Q76 42 60 35" fill="#FFEB3B"/>
                            <path d="M60 35 Q44 28 36 38 Q44 42 60 35" fill="#FFEB3B"/>
                            <!-- Tip -->
                            <path d="M60 25 Q68 18 72 25 Q68 28 60 25" fill="#FFF59D"/>
                            <path d="M60 25 Q52 18 48 25 Q52 28 60 25" fill="#FFF59D"/>
                            <!-- Arrow showing progression -->
                            <path d="M100 65 L100 30 L105 35 M100 30 L95 35" stroke="#FF5722" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Ano ang Yellowing?</h6>
                        <ul class="mb-3">
                            <li>Unti-unting nagiging dilaw ang dahon mula sa dulo</li>
                            <li>Nagsisimula sa mas matandang dahon</li>
                            <li>Pwedeng may pattern (interveinal, tip, whole leaf)</li>
                            <li>Karamihan sa halaman ay apektado</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Posibleng mga Dahilan:</strong></p>
                    <ul class="mb-3">
                        <li><strong>Nitrogen deficiency</strong> - general yellowing mula sa lumang dahon</li>
                        <li><strong>Iron deficiency</strong> - interveinal chlorosis sa bagong dahon</li>
                        <li><strong>Zinc deficiency</strong> - may dusty brown spots din</li>
                        <li><strong>Waterlogging</strong> - root damage, di makakuha ng nutrients</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-bulb me-1"></i>Payo:</h6>
                        <p class="mb-0">Ang pattern ng yellowing ay makakatulong malaman ang specific deficiency. Magpa-soil test para sa tamang diagnosis.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fast Drainage Modal -->
    <div class="modal fade soil-info-modal" id="fastDrainageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Fast Drainage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/sandy-soil.jpg') }}"
                             alt="Fast Draining Sandy Soil" class="img-fluid rounded" style="max-height: 120px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Sandy soil - water drains quickly (< 10 seconds)</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-0">
                            <li>Water disappears in less than 10 seconds</li>
                            <li>Soil is dry again within hours after rain</li>
                            <li>Plants wilt quickly without irrigation</li>
                            <li>Usually sandy or very porous soil</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Implications:</strong></p>
                    <ul>
                        <li>Need frequent irrigation</li>
                        <li>Nutrients wash out quickly</li>
                        <li>Good for crops that hate wet feet</li>
                        <li>May need split fertilizer applications</li>
                    </ul>
                    <div class="info-tip">
                        <h6><i class="bx bx-bulb me-1"></i>Tip:</h6>
                        <p>Add organic matter to improve water retention. Use drip irrigation for efficient water use.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Moderate Drainage Modal -->
    <div class="modal fade soil-info-modal" id="moderateDrainageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Moderate Drainage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/compost-soil.jpg') }}"
                             alt="Good Loam Soil" class="img-fluid rounded" style="max-height: 120px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Loamy soil - ideal balanced drainage</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-0">
                            <li>Water soaks in within minutes</li>
                            <li>Soil stays moist but not waterlogged</li>
                            <li>No standing water after rain</li>
                            <li>Plants don't wilt or get root rot</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Implications:</strong></p>
                    <ul>
                        <li>Ideal drainage for most crops</li>
                        <li>Good balance of moisture and aeration</li>
                        <li>Nutrients retained well</li>
                        <li>Standard irrigation schedule works</li>
                    </ul>
                    <div class="info-tip">
                        <h6><i class="bx bx-bulb me-1"></i>Tip:</h6>
                        <p>This is ideal! Maintain good soil structure with regular organic matter additions.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slow Drainage Modal -->
    <div class="modal fade soil-info-modal" id="slowDrainageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Slow Drainage / Waterlogged</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/clay-soil.jpg') }}"
                             alt="Clay Soil - Slow Drainage" class="img-fluid rounded" style="max-height: 120px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Clay soil - slow drainage, prone to waterlogging</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-0">
                            <li>Water pools for hours or days after rain</li>
                            <li>Soil stays muddy and soggy</li>
                            <li>Bad smell from waterlogged soil</li>
                            <li>Plants show yellowing or root rot</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Implications:</strong></p>
                    <ul>
                        <li>Risk of root diseases</li>
                        <li>Poor oxygen for roots</li>
                        <li>Nutrient deficiency from waterlogging</li>
                        <li>May need raised beds or drainage</li>
                    </ul>
                    <div class="info-tip">
                        <h6><i class="bx bx-bulb me-1"></i>Tip:</h6>
                        <p>Consider building raised beds or installing drainage. Add organic matter and gypsum to improve soil structure.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sodic/Alkaline Suspicion Modal -->
    <div class="modal fade soil-info-modal" id="sodicSuspicionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Sodic/Alkaline Soil Suspicion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/salt-affected-soil.jpg') }}"
                             alt="Sodic Alkaline Soil" class="img-fluid rounded" style="max-height: 120px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Sodic/Saline soil - white salt deposits on surface</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Signs of Sodic/Alkaline Soil:</h6>
                        <ul class="mb-0">
                            <li>White crust on soil surface</li>
                            <li>Soil pH above 7 (if tested)</li>
                            <li>Poor soil structure - dispersive</li>
                            <li>Stunted plant growth</li>
                            <li>Yellowing between leaf veins (iron deficiency)</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Common Causes:</strong></p>
                    <ul>
                        <li>High sodium content in soil</li>
                        <li>Irrigation with salty water</li>
                        <li>Poor drainage causing salt buildup</li>
                        <li>Naturally alkaline parent material</li>
                    </ul>
                    <div class="info-tip">
                        <h6><i class="bx bx-bulb me-1"></i>Tip:</h6>
                        <p>Apply gypsum (calcium sulfate) to replace sodium. Improve drainage and use acidifying fertilizers.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acidic Suspicion Modal -->
    <div class="modal fade soil-info-modal" id="acidicSuspicionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Acidic Soil Suspicion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/acid-sulfate-soil.jpg') }}"
                             alt="Acidic Soil" class="img-fluid rounded" style="max-height: 120px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Acidic soil - often reddish/orange, low pH causes nutrient lockout</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Signs of Acidic Soil:</h6>
                        <ul class="mb-0">
                            <li>Soil pH below 6 (if tested)</li>
                            <li>Yellowing leaves despite fertilization</li>
                            <li>Poor crop performance</li>
                            <li>Moss or certain weeds thrive</li>
                            <li>Reddish or orange soil color</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Common Causes:</strong></p>
                    <ul>
                        <li>Heavy rainfall leaching nutrients</li>
                        <li>Overuse of nitrogen fertilizers</li>
                        <li>Naturally acidic parent material</li>
                        <li>Aluminum toxicity possible</li>
                    </ul>
                    <div class="info-tip">
                        <h6><i class="bx bx-bulb me-1"></i>Tip:</h6>
                        <p>Apply agricultural lime to raise pH. Get a soil test to determine how much lime is needed.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compaction Suspicion Modal -->
    <div class="modal fade soil-info-modal" id="compactionSuspicionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Compaction/Hardpan Suspicion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/hardpan-soil.jpg') }}"
                             alt="Soil Compaction" class="img-fluid rounded" style="max-height: 120px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Compacted soil layer - restricts root growth and drainage</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Signs of Compaction:</h6>
                        <ul class="mb-0">
                            <li>Water pools on surface after rain</li>
                            <li>Shallow root systems</li>
                            <li>Difficult to dig or penetrate soil</li>
                            <li>Hard layer below topsoil</li>
                            <li>Plants stunted despite fertilization</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Common Causes:</strong></p>
                    <ul>
                        <li>Heavy machinery on wet soil</li>
                        <li>Repeated tillage at same depth</li>
                        <li>Lack of organic matter</li>
                        <li>Natural clay accumulation</li>
                    </ul>
                    <div class="info-tip">
                        <h6><i class="bx bx-bulb me-1"></i>Tip:</h6>
                        <p>Use deep tillage or subsoiling to break up hardpan. Reduce traffic on wet soil and add organic matter.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Organic Matter Modal -->
    <div class="modal fade soil-info-modal" id="lowOrganicModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Low Organic Matter Suspicion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/sandy-soil.jpg') }}"
                             alt="Low Organic Soil" class="img-fluid rounded" style="max-height: 120px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Light-colored soil with low organic matter content</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Signs of Low Organic Matter:</h6>
                        <ul class="mb-0">
                            <li>Light colored, pale soil</li>
                            <li>Poor soil structure</li>
                            <li>Soil crusts easily after rain</li>
                            <li>Low earthworm activity</li>
                            <li>Requires more fertilizer for same yield</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Common Causes:</strong></p>
                    <ul>
                        <li>Continuous cropping without residue return</li>
                        <li>Burning crop residues</li>
                        <li>Excessive tillage</li>
                        <li>Erosion of topsoil</li>
                    </ul>
                    <div class="info-tip">
                        <h6><i class="bx bx-bulb me-1"></i>Tip:</h6>
                        <p>Add compost, manure, or grow cover crops. Return crop residues to the soil instead of burning.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transplanted Planting System Info Modal -->
    <div class="modal fade soil-info-modal" id="transplantedInfoModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transplanted Rice (Inilipat-tanim)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <img src="{{ asset('images/recommendations/crop-methods/transplanting.webp') }}" alt="Transplanted Rice" class="img-fluid rounded" style="width:100%;max-height:200px;object-fit:cover;">
                    </div>
                    <div class="info-section section-benefits">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#transplant-benefits">
                            <h6><i class="bx bx-check-circle me-1"></i>Mga Bentahe</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="transplant-benefits">
                            <div class="info-section-body">
                                <p>Mas controlled ang weed management kasi nauna na lumaki ang seedlings bago itanim sa field. Uniform ang plant spacing kaya pantay-pantay ang growth ng bawat hill. Mas matipid din sa seeds kumpara sa direct seeding, at mas madaling i-manage ang pests at diseases habang nasa seedbed stage pa lang.</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-section section-best-for">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#transplant-bestfor">
                            <h6><i class="bx bx-target-lock me-1"></i>Pinakamainam Gamitin Kapag</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="transplant-bestfor">
                            <div class="info-section-body">
                                <p>May enough na water supply para sa flooding ng field at may available na labor force para magtanim. Maganda rin ito kapag gusto mo ng organized na plant spacing, o kapag maraming weeds sa field area mo.</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-section section-tip">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#transplant-tip">
                            <h6><i class="bx bx-bulb me-1"></i>Payo</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="transplant-tip">
                            <div class="info-section-body">
                                <p>Magtanim ng 2-3 seedlings per hill na may 20x20cm spacing. Gumamit ng younger seedlings (14-21 days old) para mas maraming tillers ang lumabas.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Direct Seeding Wet Info Modal -->
    <div class="modal fade soil-info-modal" id="directWetInfoModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Direct Seeding - Wet (Sabog sa Basang Lupa)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <img src="{{ asset('images/recommendations/crop-methods/wet-seeding.webp') }}" alt="Wet Direct Seeding" class="img-fluid rounded" style="width:100%;max-height:200px;object-fit:cover;">
                    </div>
                    <div class="info-section section-benefits">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#wet-benefits">
                            <h6><i class="bx bx-check-circle me-1"></i>Mga Bentahe</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="wet-benefits">
                            <div class="info-section-body">
                                <p>Nakakatipid sa labor kasi walang transplanting na kailangan gawin. Mas mabilis ang crop establishment at growth, at mas maaga ang harvest ng mga 7-10 days. Overall, mas mababa ang production cost nito.</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-section section-best-for">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#wet-bestfor">
                            <h6><i class="bx bx-target-lock me-1"></i>Pinakamainam Gamitin Kapag</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="wet-bestfor">
                            <div class="info-section-body">
                                <p>Kulang ang available labor o sobrang mahal ang labor cost. Maganda rin kapag limited ang time between cropping seasons, may magandang water control system ang field, at kaya mong i-manage nang maayos ang weed growth.</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-section section-tip">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#wet-tip">
                            <h6><i class="bx bx-bulb me-1"></i>Payo</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="wet-tip">
                            <div class="info-section-body">
                                <p>Gumamit ng 80-100 kg seeds per hectare. Mag-apply ng pre-emergence herbicide within 3-5 days after broadcasting para ma-control ang weeds. I-monitor lagi ang water level.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Direct Seeding Dry Info Modal -->
    <div class="modal fade soil-info-modal" id="directDryInfoModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Direct Seeding - Dry (Sabog sa Tuyong Lupa)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <img src="{{ asset('images/recommendations/crop-methods/dry-seeding.webp') }}" alt="Dry Direct Seeding" class="img-fluid rounded" style="width:100%;max-height:200px;object-fit:cover;">
                    </div>
                    <div class="info-section section-benefits">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#dry-benefits">
                            <h6><i class="bx bx-check-circle me-1"></i>Mga Bentahe</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="dry-benefits">
                            <div class="info-section-body">
                                <p>Water-saving method kasi walang flooding na required. Pwede ka magtanim kahit bago pa mag-start ang rainy season, at mas kaunting labor ang kailangan. Mas deep at strong ang root system kaya hindi madaling ma-lodge. Best option ito para sa rainfed areas na walang irrigation.</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-section section-best-for">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#dry-bestfor">
                            <h6><i class="bx bx-target-lock me-1"></i>Pinakamainam Gamitin Kapag</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="dry-bestfor">
                            <div class="info-section-body">
                                <p>Kulang o irregular ang water supply, rainfed farming ang setup mo, o upland at elevated ang location ng field. Maganda rin kapag gusto mo magtanim before the rainy season starts.</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-section section-tip">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#dry-tip">
                            <h6><i class="bx bx-bulb me-1"></i>Payo</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="dry-tip">
                            <div class="info-section-body">
                                <p>Gumamit ng 60-80 kg seeds per hectare. I-plant ng 2-3 cm deep at i-time ang planting before the expected rainfall. Importante na leveled at well-prepared ang lupa.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Single Row Corn Planting Info Modal -->
    <div class="modal fade soil-info-modal" id="singleRowInfoModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Single Row Planting (Isahang Hanay)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <img src="{{ asset('images/recommendations/crop-methods/single-rows.webp') }}" alt="Single Row Corn Planting" class="img-fluid rounded" style="width:100%;max-height:200px;object-fit:cover;">
                    </div>
                    <div class="info-section section-benefits">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#single-benefits">
                            <h6><i class="bx bx-check-circle me-1"></i>Mga Bentahe</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="single-benefits">
                            <div class="info-section-body">
                                <p>Mas madaling mag-cultivate gamit ang machine sa pagitan ng rows, at better ang air circulation kaya less chance ng disease. Simple lang i-implement at i-manage, at pwede pa itong gamitin para sa intercropping kasama ang ibang crops.</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-section section-count">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#single-count">
                            <h6><i class="bx bx-grid-alt me-1"></i>Plant Population</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="single-count">
                            <div class="info-section-body">
                                <p>Around 53,000 - 66,000 plants per hectare. Magtanim ng 2-3 seeds per hill, tapos i-thin hanggang 1 plant na lang.</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-section section-tip">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#single-tip">
                            <h6><i class="bx bx-bulb me-1"></i>Payo</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="single-tip">
                            <div class="info-section-body">
                                <p>Best para sa areas na low fertility o drought-prone. Pwedeng i-adjust ang row spacing depende sa variety at soil condition ng field mo.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Double Row Corn Planting Info Modal -->
    <div class="modal fade soil-info-modal" id="doubleRowInfoModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Double Row Planting (Dalawahang Hanay)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <img src="{{ asset('images/recommendations/crop-methods/double-rows.webp') }}" alt="Double Row Corn Planting" class="img-fluid rounded" style="width:100%;max-height:200px;object-fit:cover;">
                    </div>
                    <div class="info-section section-benefits">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#double-benefits">
                            <h6><i class="bx bx-check-circle me-1"></i>Mga Bentahe</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="double-benefits">
                            <div class="info-section-body">
                                <p>Higher plant density kaya potentially higher ang yield. Better ang light interception ng canopy para sa growth, at maluwag pa rin ang gap para makapasok ang equipment. Mas efficient ang land use kumpara sa single row.</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-section section-count">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#double-count">
                            <h6><i class="bx bx-grid-alt me-1"></i>Plant Population</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="double-count">
                            <div class="info-section-body">
                                <p>Around 75,000 - 90,000 plants per hectare. Roughly 20-30% more plants kaysa single row setup.</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-section section-tip">
                        <div class="info-section-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#double-tip">
                            <h6><i class="bx bx-bulb me-1"></i>Payo</h6>
                            <i class="bx bx-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="double-tip">
                            <div class="info-section-body">
                                <p>Best para sa fertile soil na may enough water supply. Gumamit ng hybrid varieties na designed for high-density planting at i-increase ang fertilizer application ayon sa plant population.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaf Symptom Modals -->
    <!-- Yellowing Symptom Modal -->
    <div class="modal fade soil-info-modal" id="yellowingSymptomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Yellowing (Paninilaw ng Dahon)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/chlorosis.jpg') }}"
                             alt="Leaf Yellowing Symptom" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Yellowing (chlorosis) - often indicates N deficiency</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Ano ang Yellowing?</h6>
                        <ul class="mb-3">
                            <li>Unti-unting nagiging dilaw ang dahon</li>
                            <li>Madalas nagsisimula sa mas matandang dahon (lower leaves)</li>
                            <li>Pwedeng buong dahon o may pattern (interveinal)</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Posibleng Dahilan:</strong></p>
                    <ul class="mb-3">
                        <li><strong>Nitrogen (N) deficiency</strong> - general yellowing mula sa lumang dahon</li>
                        <li><strong>Iron (Fe) deficiency</strong> - interveinal yellowing sa bagong dahon</li>
                        <li><strong>Zinc (Zn) deficiency</strong> - may dusty brown spots din</li>
                        <li><strong>Waterlogging</strong> - root damage, di makakuha ng nutrients</li>
                    </ul>
                    <div class="info-tip alert alert-info">
                        <h6><i class="bx bx-bulb me-1"></i>Payo:</h6>
                        <p class="mb-0">Mag-apply ng urea para sa nitrogen deficiency. Kung may pattern, pwedeng iron o zinc - magpa-soil test para sigurado.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Striping Symptom Modal -->
    <div class="modal fade soil-info-modal" id="stripingSymptomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Striping (May Guhit-guhit)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/maize-mosaic-virus.jpg') }}"
                             alt="Leaf Striping Symptom" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Striping pattern - may indicate Zn deficiency or viral infection</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Ano ang Striping?</h6>
                        <ul class="mb-3">
                            <li>May dilaw o puting guhit-guhit sa dahon</li>
                            <li>Karaniwang parallel sa ugat ng dahon (veins)</li>
                            <li>Madalas makikita sa bagong dahon</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Posibleng Dahilan:</strong></p>
                    <ul class="mb-3">
                        <li><strong>Zinc (Zn) deficiency</strong> - interveinal chlorosis, dusty brown necrosis</li>
                        <li><strong>Magnesium (Mg) deficiency</strong> - stripes between veins sa lumang dahon</li>
                        <li><strong>Viral infection</strong> - tungro, rice stripe virus</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-bulb me-1"></i>Payo:</h6>
                        <p class="mb-0">Kung zinc deficiency, mag-spray ng zinc sulfate. Kung suspected virus, tanggalin ang apektadong halaman at kontrolin ang mga insekto.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tip Burn Symptom Modal -->
    <div class="modal fade soil-info-modal" id="tipBurnSymptomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Tip Burn (Sunog ang Dulo)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/bacterial-leaf-blight.jpg') }}"
                             alt="Leaf Tip Burn Symptom" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Tip burn - often due to K deficiency or salt stress</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Ano ang Tip Burn?</h6>
                        <ul class="mb-3">
                            <li>Nasusunog ang dulo ng dahon (brown/dry)</li>
                            <li>Madalas nagsisimula sa tip tapos kumakain papasok</li>
                            <li>Pwedeng may dilaw na borders</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Posibleng Dahilan:</strong></p>
                    <ul class="mb-3">
                        <li><strong>Potassium (K) deficiency</strong> - marginal burn sa lumang dahon</li>
                        <li><strong>Salt toxicity</strong> - too much fertilizer o saline water</li>
                        <li><strong>High temperature stress</strong> - during extreme heat</li>
                        <li><strong>Bacterial leaf blight</strong> - may yellow halo</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-bulb me-1"></i>Payo:</h6>
                        <p class="mb-0">Check ang fertilizer application - baka sobra. Kung K deficiency, dagdagan ng muriate of potash (MOP).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stunting Symptom Modal -->
    <div class="modal fade soil-info-modal" id="stuntingSymptomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Stunting (Bansot/Di Lumalaki)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/rice-tungro.jpg') }}"
                             alt="Stunted Plant Symptom" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Stunting - may indicate nutrient deficiency, disease, or soil problems</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Ano ang Stunting?</h6>
                        <ul class="mb-3">
                            <li>Mas maliit ang halaman kumpara sa dapat</li>
                            <li>Di nag-grow kahit maraming araw na</li>
                            <li>Pwedeng may kasamang yellowing o maliliit na dahon</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Posibleng Dahilan:</strong></p>
                    <ul class="mb-3">
                        <li><strong>Multiple nutrient deficiencies</strong> - N, P, or Zn</li>
                        <li><strong>Phosphorus (P) deficiency</strong> - stunting + purpling</li>
                        <li><strong>Root problems</strong> - nematodes, root rot</li>
                        <li><strong>Viral diseases</strong> - tungro, grassy stunt</li>
                        <li><strong>Compacted soil</strong> - limited root growth</li>
                    </ul>
                    <div class="info-tip alert alert-info">
                        <h6><i class="bx bx-bulb me-1"></i>Payo:</h6>
                        <p class="mb-0">Mag-apply ng complete fertilizer (14-14-14). Check din ang soil - baka compacted o may pest. Kung widespread, possible virus.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Purpling Symptom Modal -->
    <div class="modal fade soil-info-modal" id="purplingSymptomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Purpling (P-deficiency)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/phosphorus-deficiency.jpg') }}"
                             alt="Leaf Purpling Symptom" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Purpling - classic sign of phosphorus (P) deficiency</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Ano ang Purpling?</h6>
                        <ul class="mb-3">
                            <li>Nagiging purple/reddish ang dahon</li>
                            <li>Karaniwang sa lumang dahon muna</li>
                            <li>Pwedeng kasama ang stunting</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Posibleng Dahilan:</strong></p>
                    <ul class="mb-3">
                        <li><strong>Phosphorus (P) deficiency</strong> - classic symptom, stunting + purple</li>
                        <li><strong>Cold temperature stress</strong> - nagpi-pigment ang dahon</li>
                        <li><strong>Genetic traits</strong> - some varieties naturally may purple</li>
                    </ul>
                    <div class="info-tip alert alert-success">
                        <h6><i class="bx bx-bulb me-1"></i>Payo:</h6>
                        <p class="mb-0">Mag-apply ng phosphorus-rich fertilizer (16-20-0 o DAP). Early application is key - P is important for root development.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Poor Flowering Modal -->
    <div class="modal fade soil-info-modal" id="poorFloweringModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Poor Flowering (Konti ang Bulaklak)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/rice-panicle.jpg') }}"
                             alt="Rice Flowering" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Rice panicle - poor flowering leads to reduced grain set</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Ano ang Poor Flowering?</h6>
                        <ul class="mb-3">
                            <li>Konti lang ang bulaklak o pollen</li>
                            <li>May incomplete na panicle emergence</li>
                            <li>Maraming unfilled spikelets</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Posibleng Dahilan:</strong></p>
                    <ul class="mb-3">
                        <li><strong>Boron (B) deficiency</strong> - affects pollen viability</li>
                        <li><strong>Nitrogen excess late stage</strong> - too much vegetative growth</li>
                        <li><strong>High temperature during flowering</strong> - pollen sterility</li>
                        <li><strong>Water stress</strong> - during reproductive stage</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-bulb me-1"></i>Payo:</h6>
                        <p class="mb-0">Apply boron foliar spray during booting stage. Ensure adequate water during flowering. Avoid late N application.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Poor Grain Fill Modal -->
    <div class="modal fade soil-info-modal" id="poorGrainFillModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Poor Grain Fill (Maraming Ipa)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Photo Reference -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/rice-grains.jpg') }}"
                             alt="Rice Grains" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Rice grains - poor grain fill results in "ipa" (unfilled/empty grains)</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Ano ang Poor Grain Fill?</h6>
                        <ul class="mb-3">
                            <li>Maraming ipa (unfilled grains)</li>
                            <li>Magaan ang palay pag ani</li>
                            <li>Chalky o partly filled ang grains</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Posibleng Dahilan:</strong></p>
                    <ul class="mb-3">
                        <li><strong>Potassium (K) deficiency</strong> - affects grain filling</li>
                        <li><strong>High night temperature</strong> - increases respiration</li>
                        <li><strong>Water stress during grain filling</strong> - critical stage</li>
                        <li><strong>Early senescence</strong> - leaves die before grains fill</li>
                        <li><strong>Pest damage</strong> - stink bugs, rice bugs</li>
                    </ul>
                    <div class="info-tip alert alert-primary">
                        <h6><i class="bx bx-bulb me-1"></i>Payo:</h6>
                        <p class="mb-0">Ensure K application before panicle initiation. Maintain water during grain filling. Control rice bugs during milking stage.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== PEST INFO MODALS ========== -->

    <!-- BPH (Brown Planthopper) Modal -->
    <div class="modal fade soil-info-modal" id="bphPestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Brown Planthopper (BPH)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/brown-planthopper.jpg') }}"
                             alt="Brown Planthopper" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Brown Planthopper (Nilaparvata lugens)</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Maliit na insekto, brown ang kulay, 3-4mm ang laki</li>
                            <li>Nagtitipon sa base ng halaman (tangkay malapit sa lupa)</li>
                            <li>May pakpak, pwedeng lumipad at kumalat mabilis</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li><strong>Hopperburn</strong> - nagiging dilaw at namamatay ang halaman</li>
                        <li>Suminisipsip ng katas mula sa tangkay</li>
                        <li>Nagdadala ng viral diseases (tungro, ragged stunt)</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumamit ng resistant varieties. Iwasan ang sobrang nitrogen. I-synchronize ang pagtanim sa lugar. May biological control (predators) din.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stem Borer Modal -->
    <div class="modal fade soil-info-modal" id="stemBorerPestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Rice Stem Borer (Uod sa Tangkay)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/stem-borer.jpg') }}"
                             alt="Rice Stem Borer" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Yellow Stem Borer (Scirpophaga incertulas)</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Uod sa loob ng tangkay (cream-colored larva)</li>
                            <li>Parihabang moth (puti o dilaw ang pakpak)</li>
                            <li>Makikita ang butas sa tangkay</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li><strong>Dead heart</strong> - namamatay ang central leaf sa vegetative stage</li>
                        <li><strong>White head</strong> - puti ang panicle, walang butil sa reproductive stage</li>
                        <li>Malaking kawalan ng ani kung marami ang infected</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Tanggalin at sunugin ang mga stubbles pagkatapos ng ani. Gumamit ng Trichogramma (parasitoid wasp). Light trap para sa moths.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaf Folder Modal -->
    <div class="modal fade soil-info-modal" id="leafFolderPestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Leaf Folder (Tiklop-dahon)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/leaf-folder.jpg') }}"
                             alt="Rice Leaf Folder" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Rice Leaf Folder (Cnaphalocrocis medinalis)</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Natitiklop ang dahon (parang tubo o sigarilyo)</li>
                            <li>Makikita ang green caterpillar sa loob ng tiklop</li>
                            <li>May puting guhit ang nasira na leaf tissue</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Kinakain ang upper epidermis ng dahon</li>
                        <li>Nagdudulot ng "window-like" damage (transparent patches)</li>
                        <li>Nababawasan ang leaf area para sa photosynthesis</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Iwasan ang sobrang nitrogen. May natural enemies - spiders, wasps. Kung severe, gumamit ng insecticide sa tamang dosage.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rice Bug Modal -->
    <div class="modal fade soil-info-modal" id="riceBugPestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Rice Bug (Atangya/Kuto ng Palay)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/rice-bug.jpg') }}"
                             alt="Rice Bug" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Rice Bug (Leptocorisa oratorius)</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Mahabang katawan (slender), brown o green ang kulay</li>
                            <li>May mabahong amoy kapag nahawakan (stink bug)</li>
                            <li>Active sa umaga at hapon, nagtatago sa tanghali</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Sinisipsip ang butil habang soft/milky stage pa</li>
                        <li>Nagiging "ipa" (unfilled/empty grain)</li>
                        <li>May spotty o black spots sa butil kung natira</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gawin ang "sabay-tanim" sa komunidad. Tanggalin ang mga damo na tirahan nila. Mag-spray sa umaga o hapon kung severe.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tungro Modal -->
    <div class="modal fade soil-info-modal" id="tungroPestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug-alt text-danger me-2"></i>Tungro Virus Disease</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/rice-tungro.jpg') }}"
                             alt="Rice Tungro Disease" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Rice Tungro Disease - yellow-orange discoloration</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Orange-yellow na dahon, nagsisimula sa tip</li>
                            <li>Bansot (stunted) ang halaman</li>
                            <li>Konti ang tiller, delayed ang pagbubunga</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Dala ng Green Leafhopper (GLH)</li>
                        <li>Walang gamot - viral disease ito</li>
                        <li>Maaaring mawala ang buong ani kung severe</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumamit ng tungro-resistant varieties. Bunutin at sunugin ang infected plants. Kontrolin ang GLH vectors. Sabay-sabay tanim.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rats Modal -->
    <div class="modal fade soil-info-modal" id="ratsPestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Daga (Rats)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/rats.jpg') }}"
                             alt="Rice Field Rat" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Asian House Rat (Rattus tanezumi) - common in rice fields</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>May cut tillers sa base (45° angle cut)</li>
                            <li>Kinain ang panicles/ears sa reproductive stage</li>
                            <li>May mga burrows at landas-landas sa bukid</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Kinakain ang seedlings hanggang mature na halaman</li>
                        <li>Pwedeng 5-30% yield loss kung severe</li>
                        <li>Mabilis magparami - 1 pair = 1,000+ offspring per year</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Community-based rat management. Panatilihing malinis ang pilapil. Trap Barrier System (TBS). Rodenticides sa tamang paggamit.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fall Armyworm Modal -->
    <div class="modal fade soil-info-modal" id="fallArmywormModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Fall Armyworm (Harabas)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/fall-armyworm.jpg') }}"
                             alt="Fall Armyworm" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Fall Armyworm (Spodoptera frugiperda)</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Caterpillar na may "inverted Y" mark sa head</li>
                            <li>May 4 black spots na square sa likod ng bawat segment</li>
                            <li>Madalas makita sa whorl (puso) ng mais</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Kinakain ang dahon, may mga butas na irregular</li>
                            <li>Pinapasok ang whorl at ear (corn cob)</li>
                        <li>Pwedeng 100% loss kung walang kontrol at severe</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Early detection is key! Mag-scout ng field regularly. Gumamit ng Bt corn varieties. May biocontrol agents din (Trichogramma, Metarhizium).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Corn Borer Modal -->
    <div class="modal fade soil-info-modal" id="cornBorerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Corn Borer (Uod ng Mais)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/corn-borer.jpg') }}"
                             alt="Asian Corn Borer" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Asian Corn Borer (Ostrinia furnacalis)</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Cream-colored caterpillar sa loob ng stalk</li>
                            <li>May butas sa tangkay, may "frass" (sawdust-like excrement)</li>
                            <li>Moth ay brown-yellow na may zigzag pattern</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Binubutas ang tangkay mula sa loob</li>
                        <li>"Dead heart" sa early stage, broken tassels/stalks sa late</li>
                        <li>Pinapasok din ang ear (corn cob)</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumamit ng Bt corn. Tanggalin ang stubbles after harvest. May Trichogramma wasps para sa biocontrol. Light traps para sa adults.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Aphids Modal -->
    <div class="modal fade soil-info-modal" id="aphidsPestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Aphids (Mga Dapang)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/aphids.jpg') }}"
                             alt="Aphids" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Aphids - small sap-sucking insects</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Napakaliit na insekto, green o black, nagkukumpol</li>
                            <li>Madalas nasa ilalim ng dahon at sa whorl</li>
                            <li>May "honeydew" - malagkit na secretion</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                            <li>Suminisipsip ng katas, nagdudulot ng yellowing</li>
                        <li>Nagdadala ng viral diseases</li>
                        <li>Honeydew nagdudulot ng sooty mold</li>
                    </ul>
                    <div class="info-tip alert alert-info">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">May maraming natural enemies - ladybugs, lacewings. Strong water spray pwede. Kung severe, gumamit ng insecticidal soap o neem.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cutworms Modal -->
    <div class="modal fade soil-info-modal" id="cutwormsPestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Cutworms (Harabas Lupa)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/cutworm.jpg') }}"
                             alt="Cutworm" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Cutworm (Agrotis sp.) - hides in soil during day</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Plump caterpillar, curls into "C" when disturbed</li>
                            <li>Brown, gray, or black; active sa gabi</li>
                            <li>Nakatago sa lupa o sa base ng halaman sa araw</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Pinutol ang seedlings sa soil level (cut appearance)</li>
                        <li>Active sa gabi - makikita sa umaga ang pinsala</li>
                        <li>Pwedeng mawala ang buong stand kung severe</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Good land preparation (expose larvae to sun). Collars around seedlings. Baiting (bran + insecticide) sa gabi. Tanggalin ang mga damo.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Earworm Modal -->
    <div class="modal fade soil-info-modal" id="earwormPestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Corn Earworm (Uod ng Bunga)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/earworm.jpg') }}"
                             alt="Corn Earworm" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Corn Earworm (Helicoverpa armigera)</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Caterpillar sa tip ng corn cob (ear)</li>
                            <li>Green, brown, or pink; may stripes</li>
                            <li>Makikita ang frass (excrement) sa silk</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Kinakain ang silks at developing kernels</li>
                        <li>Nagsisimula sa tip, papasukin ang ear</li>
                        <li>Creates entry point para sa fungal diseases</li>
                    </ul>
                    <div class="info-tip alert alert-info">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumamit ng Bt corn. May pheromone traps para sa monitoring. Mineral oil sa silk channel. Harvest agad kapag mature.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Green Leafhopper Modal -->
    <div class="modal fade soil-info-modal" id="greenLeafhopperModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Green Leafhopper</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/green-leafhopper.jpg') }}"
                             alt="Green Leafhopper" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Nephotettix virescens</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Maliit na berdeng insekto, 3-5mm, mabilis tumalon</li>
                            <li>Matatagpuan sa dahon at tangkay ng palay</li>
                            <li>Active sa gabi, naaakit sa ilaw</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Pangunahing tagadala ng tungro virus</li>
                        <li>Suminisipsip ng katas ng halaman</li>
                        <li>Nagdudulot ng pagkadilaw at pamumula ng dahon</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumamit ng resistant varieties (lalo na tungro-resistant). Iwasan ang maliwanag na ilaw malapit sa palayan. Synchronize ang pagtanim sa buong komunidad.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kuhol / Golden Apple Snail Modal -->
    <div class="modal fade soil-info-modal" id="kuholPestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Kuhol / Golden Apple Snail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/golden-apple-snail.jpg') }}"
                             alt="Golden Apple Snail" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Pomacea canaliculata</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Malaking freshwater snail, kulay brown o ginto</li>
                            <li>Pink na itlog sa tangkay ng halaman o dingding ng paddy</li>
                            <li>Mas active sa gabi at pagkatapos ng ulan</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Kinakain ang mga batang punla ng palay (1-30 araw)</li>
                        <li>Pwedeng ubusin ang buong seedbed sa isang gabi</li>
                        <li>Mas malala kapag mataas ang tubig sa palayan</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Kolektahin ang kuhol at itlog nang manu-mano. Panatilihin ang mababang tubig (2-3cm) pagkatapos itanim. Gumamit ng screen/net sa pasukan ng tubig. Pwedeng gawing pataba o feeds.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rice Hispa Modal -->
    <div class="modal fade soil-info-modal" id="riceHispaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Rice Hispa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/rice-hispa.jpg') }}"
                             alt="Rice Hispa" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Dicladispa armigera</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Maliit na beetle (5mm), asul-itim ang kulay, may tinik sa likod</li>
                            <li>Gumagawa ng tunnel sa loob ng dahon (leaf mining)</li>
                            <li>Makikita ang parallel na puting guhit sa dahon</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Kinakagat ng adult ang ibabaw ng dahon (scraping)</li>
                        <li>Larvae ay nagmu-mine sa loob ng dahon, nagiging puti/transparent</li>
                        <li>Nababawasan ang photosynthesis at ani</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Putulin at sunugin ang mga apektadong dahon. Iwasan ang sobrang nitrogen. Gumamit ng natural enemies tulad ng parasitoid wasps.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Black Bug Modal -->
    <div class="modal fade soil-info-modal" id="blackBugModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Black Bug</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/black-bug.jpg') }}"
                             alt="Black Bug" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Scotinophara coarctata</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Flat na insekto, hugis-shield, itim o maitim na brown, 8-9mm</li>
                            <li>May mabahong amoy kapag na-disturb</li>
                            <li>Mas marami sa panahon ng tag-ulan</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Suminisipsip ng katas mula sa base ng tangkay</li>
                        <li>"Bugburn" - nagiging dilaw at namamatay ang buong grupo ng halaman</li>
                        <li>Mas malala sa mga palayan na malapit sa ilaw (gabi)</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Iwasan ang maliwanag na ilaw sa gabi malapit sa palayan. Panatilihing malinis ang pilapil. Gumamit ng resistant varieties. I-drain ang tubig paminsan-minsan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rice Whorl Maggot Modal -->
    <div class="modal fade soil-info-modal" id="whorlMaggotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Rice Whorl Maggot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/whorl-maggot.jpg') }}"
                             alt="Rice Whorl Maggot" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Hydrellia philippina</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Maliit na grayish fly, 2mm lang ang laki</li>
                            <li>Makikita ang puting guhit o spots sa loob ng dahon</li>
                            <li>Yellow o whitish maggot sa loob ng leaf whorl</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Kinakain ng maggot ang loob ng dahon na hindi pa nagbubukas (whorl)</li>
                        <li>Nagiging may puting linya ang dahon kapag nagbukas</li>
                        <li>Mas apektado ang batang palay (seedling to tillering stage)</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Huwag mag-spray ng insecticide sa maagang stage (may natural enemies). Mag-apply ng nitrogen para maka-recover ang halaman. Usually minor pest lang ito.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mole Cricket / Kuriat Modal -->
    <div class="modal fade soil-info-modal" id="moleCricketModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Mole Cricket / Kuriat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/mole-cricket.jpg') }}"
                             alt="Mole Cricket" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Gryllotalpa orientalis</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Malaking insekto (3-5cm), brown, may malapad na front legs para humukay</li>
                            <li>Nagbu-burrow sa lupa, gumawa ng tunnel</li>
                            <li>Active sa gabi, naaakit sa ilaw</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Pinutuputol ang ugat at tangkay ng mga punla</li>
                        <li>Ginugulo ang lupa, natatanggal ang mga bago pa lang itanim</li>
                        <li>Mas malala sa seedbed at bagong tanim na palayan</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumamit ng light trap sa gabi. I-flood ang palayan bago magtanim. Bait traps gamit ang rice bran na may insecticide. Panatilihing maayos ang seedbed.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rice Armyworm Modal -->
    <div class="modal fade soil-info-modal" id="armywormRiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Rice Armyworm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/army-worm-moth.jpg') }}"
                             alt="Rice Armyworm" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Mythimna separata</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Caterpillar na green o brown, may stripes sa gilid, 3-4cm</li>
                            <li>Gumagalaw nang grupo (parang army), usually sa gabi</li>
                            <li>Adult moth ay brown, may white dot sa pakpak</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Kinakain ang mga dahon, kayang ubusin ang buong taniman</li>
                        <li>Mabilis kumalat - gumagalaw mula sa isang lugar papunta sa kabilang lugar</li>
                        <li>Mas malala pagkatapos ng drought na sinundan ng ulan</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumawa ng trench (kanal) sa paligid ng palayan para i-trap. Gumamit ng light trap para sa moths. Pulutin nang manu-mano kapag kaunti pa lang. Mag-spray ng insecticide kapag malala na.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rice Blast Modal -->
    <div class="modal fade soil-info-modal" id="riceBlastModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-error-circle text-warning me-2"></i>Rice Blast</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/rice-blast.jpg') }}"
                             alt="Rice Blast" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Pyricularia oryzae (Magnaporthe oryzae)</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Diamond-shaped o elliptical na lesion sa dahon (gray center, brown edges)</li>
                            <li>Pwedeng atakehin ang tangkay (neck blast), nodes, at panicle</li>
                            <li>Mas malala sa malamig at mahalumigmig na panahon</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li><strong>Leaf blast</strong> - maraming spots, nababawasan ang photosynthesis</li>
                        <li><strong>Neck blast</strong> - nabali ang tangkay ng panicle, walang butil o buong panicle ay puti</li>
                        <li>Pinaka-destructive na sakit ng palay sa buong mundo</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumamit ng blast-resistant varieties (may maraming available sa PhilRice). Iwasan ang sobrang nitrogen fertilizer. Balanced na fertilization (N-P-K). Mag-apply ng fungicide kung kinakailangan sa critical stages.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sheath Blight Modal -->
    <div class="modal fade soil-info-modal" id="sheathBlightModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-error-circle text-warning me-2"></i>Sheath Blight (Sakit sa Balat ng Tangkay)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/sheath-blight.jpg') }}"
                             alt="Sheath Blight" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Rhizoctonia solani</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Irregular na lesion sa sheath (balat ng tangkay), kulay green-gray na nagiging brown</li>
                            <li>Nagsisimula sa base, paakyat sa dahon</li>
                            <li>May sclerotia (maliit na brown na bilog) sa may lesion</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Nababawasan ang paggawa ng pagkain ng halaman (photosynthesis)</li>
                        <li>Nagiging mahina ang tangkay, pwedeng matumba (lodging)</li>
                        <li>Mas malala kapag siksikan ang tanim at mataas ang nitrogen</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Iwasan ang sobrang siksik na pagtanim. Balanced fertilization (huwag sobrang N). Tanggalin ang mga floating debris sa tubig. Mag-apply ng fungicide sa pagsisimula ng impeksyon.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Brown Spot Modal -->
    <div class="modal fade soil-info-modal" id="brownSpotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-error-circle text-warning me-2"></i>Brown Spot (Tiklop-tiklop na Dilaw)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/brown-spot.jpg') }}"
                             alt="Brown Spot" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Bipolaris oryzae (Cochliobolus miyabeanus)</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Oval na brown spots sa dahon, may dilaw na paligid (halo)</li>
                            <li>Spots sa butil - nagdudulot ng discoloration</li>
                            <li>Mas marami sa mahihinang halaman (kulang sa nutrients)</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Nababawasan ang photosynthesis kapag maraming spots</li>
                        <li>Nagdudulot ng pecky rice (may spots ang butil)</li>
                        <li>Senyales ng mahina/gutom na lupa (poor soil fertility)</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">I-improve ang soil fertility - mag-apply ng complete fertilizer (N-P-K-Zn). Gumamit ng healthy at treated na binhi. Proper water management. Ito ay sign na kailangan ng mas balanced na nutrition.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bacterial Leaf Blight Modal -->
    <div class="modal fade soil-info-modal" id="bacterialBlightModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-error-circle text-warning me-2"></i>Bacterial Leaf Blight / BLB</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/bacterial-leaf-streak.jpg') }}"
                             alt="Bacterial Leaf Blight" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Xanthomonas oryzae pv. oryzae</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Water-soaked na lesion sa gilid ng dahon (nagsisimula sa dulo)</li>
                            <li>Nagiging dilaw-puti ang dahon mula sa dulo papuntang base</li>
                            <li>Milky o yellowish bacterial ooze kapag pinisil ang dahon sa umaga</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Kresek (seedling blight) - biglaang pagkalanta ng buong seedling</li>
                        <li>Leaf blight - malaking bahagi ng dahon ay namamatay</li>
                        <li>Mas malala kapag may bagyo o malakas na ulan at hangin</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumamit ng BLB-resistant varieties. Iwasan ang sobrang nitrogen. Huwag mag-clip ng dahon kapag nagtatanim. Panatilihing malinis ang irrigation water. Walang effective na chemical control - prevention ang pinaka-importante.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Corn Hopper / Planthopper Modal -->
    <div class="modal fade soil-info-modal" id="cornHopperModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Corn Hopper / Planthopper</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/corn-hopper.jpg') }}"
                             alt="Corn Hopper" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Peregrinus maidis</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Maliit na insekto (3-4mm), brown o yellowish, mabilis tumalon</li>
                            <li>Nagtitipon sa mga dahon at tangkay ng mais</li>
                            <li>May pakpak, pwedeng lumipad kapag marami na</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Suminisipsip ng katas ng halaman, nagiging mahina ang mais</li>
                        <li>Pangunahing tagadala ng corn stripe virus at maize mosaic virus</li>
                        <li>Nagpo-produce ng honeydew na nagdudulot ng sooty mold</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Tanggalin ang mga damo sa paligid ng taniman. Gumamit ng resistant varieties kung available. I-monitor ang population at mag-spray ng insecticide kung kinakailangan. Panatilihin ang balanced na fertilization.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Crickets / Kuriat / Kuliglig Modal -->
    <div class="modal fade soil-info-modal" id="cricketsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-bug text-danger me-2"></i>Crickets / Kuriat / Kuliglig</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/crickets.jpg') }}"
                             alt="Crickets" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Gryllus bimaculatus / Brachytrupes portentosus</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Maitim na brown o itim na insekto, 2-4cm ang laki</li>
                            <li>May mahabang antenna at malakas na paa sa likod (para tumalon)</li>
                            <li>Active sa gabi, nagtatago sa butas ng lupa sa araw</li>
                            <li>Naririnig ang "chirping" sound sa gabi</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Pinutuputol ang base ng batang punla (seedling stage)</li>
                        <li>Kinakain ang ugat at batang dahon</li>
                        <li>Mas malala sa dry season at bagong tanim</li>
                    </ul>
                    <div class="info-tip alert alert-danger">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumamit ng light trap sa gabi para hulihin. Bait traps gamit ang rice bran o tinapay na may insecticide. I-flood ang mga butas nila sa lupa. Mag-apply ng granular insecticide sa lupa bago magtanim.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Downy Mildew Modal -->
    <div class="modal fade soil-info-modal" id="downyMildewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-error-circle text-warning me-2"></i>Downy Mildew</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/downy-mildew.jpg') }}"
                             alt="Downy Mildew" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Peronosclerospora philippinensis</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Puting downy growth (parang bulbol) sa ilalim ng dahon, usually makikita sa umaga</li>
                            <li>"Crazy top" - hindi normal ang itsura ng tassel (parang dahon ang tassel)</li>
                            <li>Mga streaks na puti-dilaw sa dahon (chlorotic striping)</li>
                            <li>Stunted at deformed ang halaman</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Pwedeng patayin ang buong halaman kung maagang na-infect</li>
                        <li>Walang ani kapag na-crazy top (walang normal na bunga)</li>
                        <li>Kumakalat mabilis sa malamig at mahalumigmig na umaga</li>
                        <li>Isa sa pinaka-destructive na sakit ng mais sa Pilipinas</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumamit ng resistant varieties (pinaka-importante). Seed treatment gamit ang metalaxyl. Tanggalin at sunugin ang mga may sakit na halaman agad. Crop rotation - huwag sunod-sunod na mais. Mag-tanim nang maaga sa season.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Corn Rust Modal -->
    <div class="modal fade soil-info-modal" id="cornRustModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-error-circle text-warning me-2"></i>Corn Rust</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/corn-rust.jpg') }}"
                             alt="Corn Rust" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Puccinia sorghi (Common Rust) / Puccinia polysora (Southern Rust)</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Maliit na reddish-brown na pustules (parang kalawang) sa dahon</li>
                            <li>Makikita sa itaas at ibaba ng dahon</li>
                            <li>Kapag pinisil, may reddish-brown na powder (spores)</li>
                            <li>Mas malala sa malamig at mahalumigmig na panahon</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Nababawasan ang photosynthesis ng dahon</li>
                        <li>Nagiging dilaw at namamatay ang mga dahon kapag malala</li>
                        <li>Nababawasan ang laki at bigat ng butil</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumamit ng rust-resistant varieties. Mag-apply ng fungicide (triazole-based) kung kinakailangan sa maagang stage. Magtanim nang maaga para maiwasan ang peak infection period. Proper spacing para sa magandang air circulation.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Corn Smut Modal -->
    <div class="modal fade soil-info-modal" id="cornSmutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-error-circle text-warning me-2"></i>Corn Smut</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/corn-smut.jpg') }}"
                             alt="Corn Smut" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Ustilago maydis</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Malaking galls (bukol) sa tassel, ear, stalk, o dahon</li>
                            <li>Nagsisimula bilang puting masa, nagiging gray-silver, tapos itim (puno ng spores)</li>
                            <li>Mas malala kapag may sugat o damage sa halaman (hail, insect, mechanical)</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Pinapalitan ang mga kernels ng malaking galls (walang butil)</li>
                        <li>Nababawasan ang yield depende sa kung saan ang gall</li>
                        <li>Ang itim na spores ay kumakalat sa iba pang halaman</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Iwasan ang mechanical damage sa halaman. Tanggalin ang mga galls bago maging itim (bago mag-release ng spores). Crop rotation - huwag sunod-sunod na mais sa parehong lupa. Balanced fertilization.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stalk Rot Modal -->
    <div class="modal fade soil-info-modal" id="stalkRotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-error-circle text-warning me-2"></i>Stalk Rot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/recommendations/modal-photos/stalk-rot.jpg') }}"
                             alt="Stalk Rot" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;">
                        <small class="d-block text-secondary mt-1">Fusarium verticillioides / Macrophomina phaseolina</small>
                    </div>
                    <div class="info-signs">
                        <h6><i class="bx bx-search-alt me-2"></i>Paano Makilala:</h6>
                        <ul class="mb-3">
                            <li>Nagiging brown o itim ang base ng tangkay (stalk)</li>
                            <li>Kapag biniyak ang tangkay, makikita ang itim o brown na loob (rotting pith)</li>
                            <li>Ang halaman ay biglang natutumba (lodging) bago i-harvest</li>
                            <li>Mas malala pagkatapos ng drought na sinundan ng ulan</li>
                        </ul>
                    </div>
                    <p class="text-dark"><strong>Pinsala:</strong></p>
                    <ul class="mb-3">
                        <li>Hindi makakuha ng nutrients at tubig ang halaman (naputol ang supply)</li>
                        <li>Premature na pamamatay ng halaman, maliit ang butil</li>
                        <li>Nagdudulot ng aflatoxin contamination sa butil (Fusarium)</li>
                        <li>Malaking yield loss dahil sa lodging</li>
                    </ul>
                    <div class="info-tip alert alert-warning">
                        <h6><i class="bx bx-shield-quarter me-1"></i>Payo:</h6>
                        <p class="mb-0">Gumamit ng resistant varieties. Balanced fertilization (iwasan sobrang N, sapat na K). Proper plant density - huwag sobrang siksik. Harvest agad kapag mature. I-manage ang insect pests na nagdudulot ng wounds sa stalk.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Variety Detail Modal -->
    <div class="modal fade" id="varietyDetailModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="varietyDetailModalLabel">
                        <i class="bx bx-leaf text-success me-2"></i>Variety Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- Loading State -->
                    <div id="variety-detail-loading" class="text-center py-5">
                        <i class="bx bx-loader-alt bx-spin" style="font-size: 3rem; color: #556ee6;"></i>
                        <p class="text-secondary mt-3 mb-0">Loading variety details...</p>
                    </div>

                    <!-- Content with Tabs -->
                    <div id="variety-detail-content" class="d-none">
                        <!-- Tabs Navigation -->
                        <ul class="nav nav-tabs modal-tabs" id="varietyDetailTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-panel" type="button" role="tab">
                                    <i class="bx bx-detail me-1"></i>Details
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="compare-tab" data-bs-toggle="tab" data-bs-target="#compare-panel" type="button" role="tab">
                                    <i class="bx bx-git-compare me-1"></i>Compare
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="varietyDetailTabContent">
                            <!-- Details Tab -->
                            <div class="tab-pane fade show active p-4" id="details-panel" role="tabpanel">
                                <!-- Header with Image -->
                                <div class="variety-detail-header">
                                    <div id="variety-detail-image-container">
                                        <!-- Image or placeholder will be inserted here -->
                                    </div>
                                    <div class="variety-detail-title flex-grow-1">
                                        <h4 id="variety-detail-name">-</h4>
                                        <p class="text-secondary mb-2" id="variety-detail-manufacturer">-</p>
                                        <div>
                                            <span class="badge bg-primary me-1" id="variety-detail-crop-badge">-</span>
                                            <span class="badge bg-info text-white" id="variety-detail-breed-badge">-</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Details Grid -->
                                <div class="variety-detail-grid">
                                    <div class="variety-detail-item">
                                        <div class="variety-detail-label"><i class="bx bx-trending-up me-1"></i>Potential Yield</div>
                                        <div class="variety-detail-value" id="variety-detail-yield">-</div>
                                    </div>
                                    <div class="variety-detail-item">
                                        <div class="variety-detail-label"><i class="bx bx-calendar me-1"></i>Days to Maturity</div>
                                        <div class="variety-detail-value" id="variety-detail-maturity">-</div>
                                    </div>
                                    <div class="variety-detail-item full-width" id="variety-detail-genes-container">
                                        <div class="variety-detail-label"><i class="bx bx-shield me-1"></i>Gene Protection</div>
                                        <div class="variety-detail-value variety-gene-badges" id="variety-detail-genes">-</div>
                                    </div>
                                    <div class="variety-detail-item full-width" id="variety-detail-characteristics-container">
                                        <div class="variety-detail-label"><i class="bx bx-list-ul me-1"></i>Characteristics</div>
                                        <div class="variety-detail-value variety-detail-characteristics" id="variety-detail-characteristics">-</div>
                                    </div>
                                    <div class="variety-detail-item full-width" id="variety-detail-info-container">
                                        <div class="variety-detail-label"><i class="bx bx-info-circle me-1"></i>Related Information</div>
                                        <div class="variety-detail-value variety-detail-characteristics" id="variety-detail-info">-</div>
                                    </div>
                                    <div class="variety-detail-item full-width" id="variety-detail-source-container">
                                        <div class="variety-detail-label"><i class="bx bx-link me-1"></i>Source</div>
                                        <div class="variety-detail-value" id="variety-detail-source">-</div>
                                    </div>
                                </div>

                                <!-- Brochure Section (shown only if brochure exists) -->
                                <div id="variety-detail-brochure-section" class="d-none mt-4">
                                    <div class="brochure-section-header">
                                        <h6 class="mb-0"><i class="bx bx-file-blank text-primary me-2"></i>Product Brochure</h6>
                                        <div class="brochure-actions">
                                            <a href="#" id="brochure-download-btn" class="btn btn-primary btn-sm" target="_blank">
                                                <i class="bx bx-download me-1"></i>Download
                                            </a>
                                            <a href="#" id="brochure-new-tab-btn" class="btn btn-outline-secondary btn-sm" target="_blank">
                                                <i class="bx bx-link-external me-1"></i>Open
                                            </a>
                                        </div>
                                    </div>
                                    <div class="brochure-preview-container">
                                        <iframe id="brochure-pdf-frame" class="brochure-pdf-frame"></iframe>
                                    </div>
                                </div>
                            </div>

                            <!-- Compare Tab -->
                            <div class="tab-pane fade p-4" id="compare-panel" role="tabpanel">
                                <!-- Search Section -->
                                <div id="compare-search-section">
                                    <div class="compare-search-header mb-3">
                                        <h6 class="text-dark mb-1"><i class="bx bx-search-alt me-2"></i>Search Another Variety to Compare</h6>
                                        <p class="text-secondary small mb-0">Search and select a variety, then switch between tabs to compare details.</p>
                                    </div>
                                    <div class="compare-search-container mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                                            <input type="text" class="form-control" id="compare-search-input" placeholder="Type to search varieties...">
                                            <button type="button" class="btn btn-outline-secondary d-none" id="compare-clear-search">
                                                <i class="bx bx-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Search Results -->
                                    <div id="compare-search-results" class="compare-search-results">
                                        <div class="compare-search-placeholder">
                                            <i class="bx bx-bulb"></i>
                                            <p class="text-dark mb-1">Enter a search term to find varieties</p>
                                            <small class="text-secondary">Results will appear here</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Variety Details (shown after selection) -->
                                <div id="compare-selected-section" class="d-none">
                                    <div class="compare-back-btn mb-3" id="compare-back-to-search">
                                        <i class="bx bx-arrow-back"></i>
                                        <span>Back to Search</span>
                                    </div>

                                    <!-- Header with Image -->
                                    <div class="variety-detail-header">
                                        <div id="compare-detail-image-container">
                                            <!-- Image or placeholder will be inserted here -->
                                        </div>
                                        <div class="variety-detail-title flex-grow-1">
                                            <h4 id="compare-detail-name">-</h4>
                                            <p class="text-secondary mb-2" id="compare-detail-manufacturer">-</p>
                                            <div>
                                                <span class="badge bg-primary me-1" id="compare-detail-crop-badge">-</span>
                                                <span class="badge bg-info text-white" id="compare-detail-breed-badge">-</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Details Grid -->
                                    <div class="variety-detail-grid">
                                        <div class="variety-detail-item">
                                            <div class="variety-detail-label"><i class="bx bx-trending-up me-1"></i>Potential Yield</div>
                                            <div class="variety-detail-value" id="compare-detail-yield">-</div>
                                        </div>
                                        <div class="variety-detail-item">
                                            <div class="variety-detail-label"><i class="bx bx-calendar me-1"></i>Days to Maturity</div>
                                            <div class="variety-detail-value" id="compare-detail-maturity">-</div>
                                        </div>
                                        <div class="variety-detail-item full-width" id="compare-detail-genes-container">
                                            <div class="variety-detail-label"><i class="bx bx-shield me-1"></i>Gene Protection</div>
                                            <div class="variety-detail-value variety-gene-badges" id="compare-detail-genes">-</div>
                                        </div>
                                        <div class="variety-detail-item full-width" id="compare-detail-characteristics-container">
                                            <div class="variety-detail-label"><i class="bx bx-list-ul me-1"></i>Characteristics</div>
                                            <div class="variety-detail-value variety-detail-characteristics" id="compare-detail-characteristics">-</div>
                                        </div>
                                        <div class="variety-detail-item full-width" id="compare-detail-info-container">
                                            <div class="variety-detail-label"><i class="bx bx-info-circle me-1"></i>Related Information</div>
                                            <div class="variety-detail-value variety-detail-characteristics" id="compare-detail-info">-</div>
                                        </div>
                                    </div>

                                    <!-- Select This Instead Button -->
                                    <div class="text-center mt-4">
                                        <button type="button" class="btn btn-primary" id="select-compare-variety">
                                            <i class="bx bx-check me-1"></i>Select This Variety Instead
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-success" id="select-variety-from-modal">
                        <i class="bx bx-check me-1"></i>Select This Variety
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Variety Finder Wizard Modal -->
    <div class="modal fade" id="varietyFinderModal" tabindex="-1" aria-labelledby="varietyFinderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="varietyFinderModalLabel">
                        <i class="bx bx-magic me-2"></i>Smart Technician Variety Finder
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Progress Dots -->
                    <div class="finder-wizard-progress">
                        <div class="progress-dot active" data-step="1"></div>
                        <div class="progress-dot" data-step="2"></div>
                        <div class="progress-dot" data-step="3"></div>
                    </div>

                    <!-- Step 1: Describe Your Needs (NEW) -->
                    <div class="finder-step active" data-step="1">
                        <div class="finder-step-header">
                            <div class="step-number">1</div>
                            <h5>Ilarawan ang hinahanap mo</h5>
                            <p>Sabihin sa sarili mong salita kung anong klase ng variety ang kailangan mo (opsyonal)</p>
                        </div>
                        <div class="finder-freetext-section">
                            <textarea class="form-control finder-freetext" id="finder-freetext" rows="4" placeholder="Halimbawa: Kailangan ko ng variety na matibay sa tagtuyot at mataas ang ani. Ang bukid ko ay nasa Isabela, mabundok ang lugar. Madalas may peste na stem borer at rice blast sa aming area. Mabigat din ang ulan tuwing tag-ulan..."></textarea>
                            <small class="text-secondary d-block mt-2">
                                <i class="bx bx-info-circle me-1"></i>Susuriin ng aming Smart Technician ang iyong mga pangangailangan at hahanapin ang pinakamainam na varieties. Pwede ka ring sumagot sa mga tanong sa ibaba para sa mas tumpak na resulta.
                            </small>
                        </div>
                        <div class="finder-skip-section">
                            <button type="button" class="btn btn-link text-secondary finder-skip-btn" data-skip-to="2">
                                <i class="bx bx-skip-next me-1"></i>Laktawan ang hakbang na ito
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Budget -->
                    <div class="finder-step" data-step="2">
                        <div class="finder-step-header">
                            <div class="step-number">2</div>
                            <h5>Magkano ang budget mo sa binhi?</h5>
                            <p>Piliin ang saklaw ng budget mo bawat ektarya</p>
                        </div>
                        <div class="finder-option-grid">
                            <div class="finder-option" data-value="low" data-field="budget">
                                <div class="option-icon">💰</div>
                                <div class="option-title">Matipid</div>
                                <div class="option-desc">Mas mababa sa ₱3,000/ha</div>
                            </div>
                            <div class="finder-option" data-value="medium" data-field="budget">
                                <div class="option-icon">💵</div>
                                <div class="option-title">Katamtaman</div>
                                <div class="option-desc">₱3,000 - ₱6,000/ha</div>
                            </div>
                            <div class="finder-option" data-value="high" data-field="budget">
                                <div class="option-icon">💎</div>
                                <div class="option-title">Premium</div>
                                <div class="option-desc">₱6,000 - ₱10,000/ha</div>
                            </div>
                            <div class="finder-option" data-value="any" data-field="budget">
                                <div class="option-icon">🎯</div>
                                <div class="option-title">Pinakamainam</div>
                                <div class="option-desc">Kahit anong budget, pinakamainam na resulta</div>
                            </div>
                        </div>
                        <div class="finder-skip-section">
                            <button type="button" class="btn btn-link text-secondary finder-skip-btn" data-skip-to="3">
                                <i class="bx bx-skip-next me-1"></i>Laktawan ang hakbang na ito
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Protection Needs -->
                    <div class="finder-step" data-step="3">
                        <div class="finder-step-header">
                            <div class="step-number">3</div>
                            <h5>Anong proteksyon ang kailangan mo?</h5>
                            <p>Piliin ang mga hamong madalas mong nararanasan (pwedeng marami ang piliin)</p>
                        </div>
                        <div class="finder-checkbox-grid">
                            <label class="finder-checkbox" data-value="drought">
                                <input type="checkbox" name="protection[]" value="drought">
                                <span class="checkbox-mark"><i class="bx bx-check"></i></span>
                                <span class="checkbox-label">🌵 Matibay sa Tagtuyot</span>
                            </label>
                            <label class="finder-checkbox" data-value="pest">
                                <input type="checkbox" name="protection[]" value="pest">
                                <span class="checkbox-mark"><i class="bx bx-check"></i></span>
                                <span class="checkbox-label">🐛 Matibay sa Peste</span>
                            </label>
                            <label class="finder-checkbox" data-value="disease">
                                <input type="checkbox" name="protection[]" value="disease">
                                <span class="checkbox-mark"><i class="bx bx-check"></i></span>
                                <span class="checkbox-label">🦠 Matibay sa Sakit</span>
                            </label>
                            <label class="finder-checkbox" data-value="lodging">
                                <input type="checkbox" name="protection[]" value="lodging">
                                <span class="checkbox-mark"><i class="bx bx-check"></i></span>
                                <span class="checkbox-label">💨 Matibay sa Hangin</span>
                            </label>
                            <label class="finder-checkbox" data-value="flood">
                                <input type="checkbox" name="protection[]" value="flood">
                                <span class="checkbox-mark"><i class="bx bx-check"></i></span>
                                <span class="checkbox-label">🌊 Matibay sa Baha</span>
                            </label>
                            <label class="finder-checkbox" data-value="none">
                                <input type="checkbox" name="protection[]" value="none">
                                <span class="checkbox-mark"><i class="bx bx-check"></i></span>
                                <span class="checkbox-label">✅ Walang partikular na kailangan</span>
                            </label>
                        </div>
                        <div class="finder-skip-section">
                            <button type="button" class="btn btn-link text-secondary finder-skip-btn" data-skip-to="results">
                                <i class="bx bx-skip-next me-1"></i>Laktawan ang hakbang na ito
                            </button>
                        </div>
                    </div>

                    <!-- Results Step -->
                    <div class="finder-step" data-step="results">
                        <div class="finder-step-header">
                            <div class="step-number"><img src="{{ $avatarSettings->avatar_url }}" alt="Smart Technician" class="smart-tech-avatar"></div>
                            <h5>Mga Rekomendasyon ng Smart Technician</h5>
                            <p class="finder-results-subtitle">Sinusuri ng aming Smart Technician ang iyong mga pangangailangan...</p>
                        </div>
                        <div class="finder-results">
                            <div class="finder-results-loading">
                                <div class="ai-loading-animation">
                                    <img src="{{ $avatarSettings->avatar_url }}" alt="Smart Technician" class="smart-tech-avatar">
                                </div>
                                <p class="text-dark fw-semibold mb-1">Sinusuri ng Smart Technician ang mga varieties...</p>
                                <p class="text-secondary small mb-0">Sandali lang po, mabilis lang ito</p>
                            </div>
                            <div class="finder-ai-summary d-none">
                                <div class="ai-summary-box">
                                    <i class="bx bx-bulb"></i>
                                    <p id="finder-ai-summary-text"></p>
                                </div>
                            </div>
                            <div class="finder-results-list d-none"></div>
                            <div class="finder-no-results d-none">
                                <i class="bx bx-search-alt"></i>
                                <h6 class="text-dark">Walang nahanap na katugma</h6>
                                <p class="text-secondary" id="finder-error-message">Subukang mag-browse ng mga varieties nang manu-mano.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="finder-navigation">
                        <button type="button" class="btn btn-outline-secondary" id="finder-prev-btn" disabled>
                            <i class="bx bx-chevron-left me-1"></i>Bumalik
                        </button>
                        <button type="button" class="btn btn-primary" id="finder-next-btn">
                            Susunod<i class="bx bx-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Finder Validation Error Modal -->
    <div class="modal fade" id="finderValidationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border: 2px solid #f46a6a; box-shadow: 0 8px 24px rgba(244, 106, 106, 0.3);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-danger"><i class="bx bx-error-circle me-2"></i>Kulang ang Impormasyon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="text-dark mb-0" id="finderValidationMessage">Mangyaring sagutan ang kahit isang hakbang o ilarawan ang iyong hinahanap sa text area bago kumuha ng rekomendasyon.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Sige, babalik ako</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Smooth section show/hide helper functions
    function showSection($el, animationType = 'fade') {
        if (!$el.length) return;

        // Remove any existing animation classes
        $el.removeClass('section-fade-out section-slide-up d-none');

        // Add animation class
        if (animationType === 'slide') {
            $el.addClass('section-slide-down');
        } else {
            $el.addClass('section-fade-in');
        }

        // Clean up animation class after animation ends
        setTimeout(function() {
            $el.removeClass('section-fade-in section-slide-down');
        }, 350);
    }

    function hideSection($el, animationType = 'fade', callback = null) {
        if (!$el.length || $el.hasClass('d-none')) {
            if (callback) callback();
            return;
        }

        // Add animation class
        if (animationType === 'slide') {
            $el.addClass('section-slide-up');
        } else {
            $el.addClass('section-fade-out');
        }

        // Hide after animation and call callback
        setTimeout(function() {
            $el.addClass('d-none').removeClass('section-fade-out section-slide-up');
            if (callback) callback();
        }, 250);
    }

    // Quick helper for switching between two sections
    function switchSections($hideEl, $showEl, animationType = 'fade') {
        hideSection($hideEl, animationType, function() {
            showSection($showEl, animationType);
        });
    }

    // Fix modal backdrop z-index issue
    // Move modal elements directly to body to escape any stacking context
    $(document).ready(function() {
        $('.soil-info-modal').appendTo('body');
        $('#varietyDetailModal').appendTo('body');
        $('#varietyFinderModal').appendTo('body');
        $('#finderValidationModal').appendTo('body');
    });

    // Variety list data storage
    let varietiesData = [];

    // Wizard variables
    let currentStep = 1;
    const totalSteps = 21;
    const skippedSteps = []; // No steps are skipped
    let isAnimating = false;

    // Show step function with direction-based animation
    function showStep(step, direction = null) {
        // Validate step bounds
        if (step < 1) step = 1;
        if (step > totalSteps) step = totalSteps;

        // Prevent rapid clicks during animation
        if (isAnimating) return;

        // Determine direction if not specified
        if (direction === null) {
            direction = step > currentStep ? 'forward' : 'back';
        }

        // If same step, do nothing
        if (step === currentStep) return;

        isAnimating = true;

        const $currentStepEl = $(`#step-${currentStep}`);
        const $targetStepEl = $(`#step-${step}`);

        // Add slide-out animation to current step
        const slideOutClass = direction === 'forward' ? 'slide-out-left' : 'slide-out-right';
        const slideInClass = direction === 'forward' ? 'slide-in-right' : 'slide-in-left';

        $currentStepEl.addClass(slideOutClass);

        // After slide-out animation, switch steps
        setTimeout(() => {
            // Hide current step
            $currentStepEl.addClass('d-none').removeClass(slideOutClass);

            // Prepare target step for slide-in
            $targetStepEl.removeClass('d-none').addClass(slideInClass);

            // Update progress bar with smooth transition
            const progress = (step / totalSteps) * 100;
            $('#wizard-progress').css('width', progress + '%');

            // Update step indicators
            $('.step-label').removeClass('active completed');
            for (let i = 1; i < step; i++) {
                $(`.step-label[data-step="${i}"]`).addClass('completed');
            }
            $(`.step-label[data-step="${step}"]`).addClass('active');

            // Update navigation buttons
            $('#prev-btn').toggle(step > 1);
            $('#next-btn').toggleClass('d-none', step === totalSteps);
            $('#submit-btn').toggleClass('d-none', step !== totalSteps);

            currentStep = step;

            // Update season images based on selected crop when entering step 6
            if (step === 6) {
                var cropType = $('#crop_type').val();
                var imgAttr = (cropType === 'corn') ? 'data-corn-src' : 'data-rice-src';
                $('.season-crop-img').each(function() {
                    $(this).attr('src', $(this).attr(imgAttr));
                });
            }

            // Scroll to top of card
            $('.card-body')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });

            // Remove slide-in class after animation completes
            setTimeout(() => {
                $targetStepEl.removeClass(slideInClass);
                isAnimating = false;
            }, 400);
        }, 200);
    }

    // Validate current step
    function validateStep(step) {
        if (step === 1) {
            const cropType = $('#crop_type').val();
            if (!cropType) {
                toastr.warning('Please select a crop to continue.', 'Selection Required');
                // Add shake animation to boxes
                $('.crop-selection-box').addClass('shake-animation');
                setTimeout(() => {
                    $('.crop-selection-box').removeClass('shake-animation');
                }, 500);
                return false;
            }
        }
        if (step === 2) {
            const cropType = $('#crop_type').val();
            const breedType = $('#breed_type').val();
            const cornType = $('#corn_type').val();
            const varietyId = $('#variety_id').val();

            // Check if breed/corn type is selected
            if (cropType === 'palay' && !breedType) {
                toastr.warning('Mangyaring pumili ng uri ng binhi (Inbred o Hybrid) para magpatuloy.', 'Kailangan Pumili');
                $('.breed-selection-box[data-crop="rice"]').addClass('shake-animation');
                setTimeout(() => {
                    $('.breed-selection-box').removeClass('shake-animation');
                }, 500);
                return false;
            }

            if (cropType === 'corn' && !cornType) {
                toastr.warning('Mangyaring pumili ng uri ng mais (Yellow o White) para magpatuloy.', 'Kailangan Pumili');
                $('.breed-selection-box[data-crop="corn"]').addClass('shake-animation');
                setTimeout(() => {
                    $('.breed-selection-box').removeClass('shake-animation');
                }, 500);
                return false;
            }

            // Check if variety is selected
            if (!varietyId) {
                toastr.warning('Please select a variety to continue.', 'Selection Required');
                $('#variety_search').focus();
                return false;
            }

            // If "Others" is selected, validate manual entry fields
            if (varietyId === 'others') {
                const manualVarietyName = $('#manual_variety_name').val().trim();
                if (!manualVarietyName) {
                    toastr.warning('Please enter the variety name.', 'Required Field');
                    $('#manual_variety_name').addClass('is-invalid').focus();
                    return false;
                } else {
                    $('#manual_variety_name').removeClass('is-invalid');
                }
            }
        }
        if (step === 3) {
            // Planting system is required
            const cropType = $('#crop_type').val();
            const ricePlantingSystem = $('#rice_planting_system').val();
            const cornPlantingSystem = $('#corn_planting_system').val();

            if (cropType === 'palay' && !ricePlantingSystem) {
                toastr.warning('Please select a rice planting system to continue.', 'Selection Required');
                $('.planting-system-box[data-crop="rice"]').addClass('shake-animation');
                setTimeout(() => {
                    $('.planting-system-box').removeClass('shake-animation');
                }, 500);
                return false;
            }

            if (cropType === 'corn' && !cornPlantingSystem) {
                toastr.warning('Please select a corn planting system to continue.', 'Selection Required');
                $('.planting-system-box[data-crop="corn"]').addClass('shake-animation');
                setTimeout(() => {
                    $('.planting-system-box').removeClass('shake-animation');
                }, 500);
                return false;
            }
        }
        if (step === 4) {
            // Farm size is required
            const farmSize = $('#farm_size_input').val();
            if (!farmSize || farmSize.trim() === '') {
                toastr.warning('Please enter your farm size to continue.', 'Farm Size Required');
                $('#farm_size_input').addClass('is-invalid').focus();
                return false;
            }
            if (parseFloat(farmSize) <= 0) {
                toastr.warning('Please enter a valid farm size greater than 0.', 'Invalid Input');
                $('#farm_size_input').addClass('is-invalid').focus();
                return false;
            }
            $('#farm_size_input').removeClass('is-invalid');
            // Update hidden fields before moving
            $('#farm_size').val(farmSize);
            $('#farm_unit').val($('#farm_unit_select').val());
        }
        if (step === 5) {
            // Location: Province and Municipality are required
            const province = $('#province_select').val();
            const municipality = $('#municipality_select').val();

            if (!province) {
                toastr.warning('Pumili ng probinsya kung saan matatagpuan ang bukid mo.', 'Kailangan ang Probinsya');
                $('#province_select').addClass('is-invalid').focus();
                $('#province-wrapper').addClass('shake-animation');
                setTimeout(() => {
                    $('#province-wrapper').removeClass('shake-animation');
                }, 500);
                return false;
            } else {
                $('#province_select').removeClass('is-invalid');
            }

            if (!municipality) {
                toastr.warning('Pumili ng munisipalidad o lungsod kung saan matatagpuan ang bukid mo.', 'Kailangan ang Munisipalidad');
                $('#municipality_select').addClass('is-invalid').focus();
                $('#municipality-wrapper').addClass('shake-animation');
                setTimeout(() => {
                    $('#municipality-wrapper').removeClass('shake-animation');
                }, 500);
                return false;
            } else {
                $('#municipality_select').removeClass('is-invalid');
            }

            // Update hidden fields
            $('#province').val(province);
            $('#municipality').val(municipality);
        }
        if (step === 6) {
            // Season selection is required
            const season = $('#cropping_season').val();
            if (!season) {
                toastr.warning('Pumili ng cropping season para magpatuloy.', 'Kailangan Pumili');
                $('.season-selection-box').addClass('shake-animation');
                setTimeout(() => {
                    $('.season-selection-box').removeClass('shake-animation');
                }, 500);
                return false;
            }
        }
        if (step === 7) {
            // Yield history answer is required
            const yieldHistory = $('#has_low_yield_history').val();
            if (!yieldHistory) {
                toastr.warning('Pumili ng sagot kung madalas ba ang mababang ani.', 'Kailangan Pumili');
                $('.yield-answer-box').addClass('shake-animation');
                setTimeout(() => {
                    $('.yield-answer-box').removeClass('shake-animation');
                }, 500);
                return false;
            }

            // If "yes" was selected, validate low yield reasons
            if (yieldHistory === 'yes') {
                // Validate low yield reasons - at least one must be selected
                const lowYieldReasons = $('#low_yield_reasons').val();
                if (!lowYieldReasons || lowYieldReasons.trim() === '') {
                    toastr.warning('Kung mababa ang ani, pumili ng kahit isang dahilan.', 'Selection Required');
                    $('.reason-box').addClass('shake-animation');
                    setTimeout(() => {
                        $('.reason-box').removeClass('shake-animation');
                    }, 500);
                    // Scroll to the reasons section
                    $('html, body').animate({
                        scrollTop: $('.yield-reasons-container').offset().top - 100
                    }, 300);
                    return false;
                }
            }
        }
        if (step === 8) {
            // Soil type selection is required
            const soilType = $('#soil_type').val();
            if (!soilType) {
                toastr.warning('Please select your soil type to continue.', 'Selection Required');
                $('.soil-selection-box').addClass('shake-animation');
                setTimeout(() => {
                    $('.soil-selection-box').removeClass('shake-animation');
                }, 500);
                return false;
            }
        }
        if (step === 9) {
            // Personal yield - required
            const avgYield = $('#average_yield_input').val();
            if (!avgYield || avgYield.trim() === '' || parseFloat(avgYield) <= 0) {
                toastr.warning('Ilagay ang iyong average na ani para makapagpatuloy.', 'Yield Required');
                $('#average_yield_input').addClass('is-invalid').focus();
                return false;
            }
            $('#average_yield_input').removeClass('is-invalid');
            // Update hidden fields
            $('#average_yield').val(avgYield);
            $('#yield_unit').val($('#yield_unit_select').val());
        }
        // Step 10: Neighbor yield - skippable, no validation required
        // Step 11: pH Clue / Soil Indicators - optional, no validation required
        if (step === 12) {
            // Soil test - must answer Oo or Wala
            const hasSoilTest = $('#has_soil_test').val();
            if (!hasSoilTest) {
                toastr.warning('Pumili kung meron ka bang soil test result.', 'Selection Required');
                $('.soil-test-answer-box').addClass('shake-animation');
                setTimeout(() => {
                    $('.soil-test-answer-box').removeClass('shake-animation');
                }, 500);
                return false;
            }
        }
        if (step === 13) {
            // Drainage - selection required
            const soilDrainage = $('#soil_drainage').val();
            if (!soilDrainage) {
                toastr.warning('Please select your soil drainage to continue.', 'Selection Required');
                $('.drainage-selection-box').addClass('shake-animation');
                setTimeout(() => {
                    $('.drainage-selection-box').removeClass('shake-animation');
                }, 500);
                return false;
            }
        }
        if (step === 14) {
            // Soil problems/suspicions - at least one selection required (can be "none")
            const soilProblems = $('#soil_problems').val();
            if (!soilProblems) {
                toastr.warning('Please select at least one option (or "Walang Hinala" if no problems).', 'Selection Required');
                $('.suspicion-box').addClass('shake-animation');
                setTimeout(() => {
                    $('.suspicion-box').removeClass('shake-animation');
                }, 500);
                return false;
            }
        }
        if (step === 15) {
            // Irrigation Type - selection required
            const irrigationType = $('#irrigation_type').val();
            if (!irrigationType) {
                toastr.warning('Please select your irrigation type to continue.', 'Selection Required');
                $('.irrigation-box:not(.reliability-box)').addClass('shake-animation');
                setTimeout(() => {
                    $('.irrigation-box').removeClass('shake-animation');
                }, 500);
                return false;
            }
        }
        if (step === 16) {
            // Water Reliability - selection required
            const waterReliability = $('#water_reliability').val();
            if (!waterReliability) {
                toastr.warning('Please select water reliability to continue.', 'Selection Required');
                $('.reliability-box').addClass('shake-animation');
                setTimeout(() => {
                    $('.reliability-box').removeClass('shake-animation');
                }, 500);
                return false;
            }
        }
        if (step === 17) {
            // Main goal selection is required
            const mainGoal = $('#main_goal').val();
            if (!mainGoal) {
                toastr.warning('Please select your main farming goal to continue.', 'Selection Required');
                $('.goal-selection-box').addClass('shake-animation');
                setTimeout(() => {
                    $('.goal-selection-box').removeClass('shake-animation');
                }, 500);
                return false;
            }
        }
        if (step === 21) {
            const sprayApproach = $('#spray_approach').val();
            if (!sprayApproach) {
                toastr.warning('Pumili ng paraan ng pag-spray para makapagpatuloy.', 'Selection Required');
                $('.spray-approach-box').addClass('shake-animation');
                setTimeout(() => {
                    $('.spray-approach-box').removeClass('shake-animation');
                }, 500);
                return false;
            }
        }
        return true;
    }

    // Crop selection handler
    $('.crop-selection-box').on('click', function() {
        const $this = $(this);
        const cropType = $this.data('crop');

        // Remove selected from all boxes
        $('.crop-selection-box').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#crop_type').val(cropType);

        // Update Step 2 for the selected crop
        updateStep2ForCrop(cropType);

        // Update Step 3 for the selected crop (planting system)
        updateStep3ForCrop(cropType);

        // Update hint text
        const cropName = cropType === 'palay' ? 'Palay (Rice)' : 'Mais (Corn)';
        $('#crop-selection-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected: <strong>${cropName}</strong>`);

        // Update Step 18 pest sections based on crop
        updateStep18ForCrop(cropType);

        // Update Step 9 yield info note based on crop
        if (cropType === 'palay') {
            $('#yield-info-note').html('<i class="bx bx-info-circle text-primary me-1"></i><small class="text-secondary">1 cavan = 50kg. Average Philippine rice yield: 80-100 cavans/hectare</small>');
        } else if (cropType === 'corn') {
            $('#yield-info-note').html('<i class="bx bx-info-circle text-primary me-1"></i><small class="text-secondary">1 cavan = 50kg. Average Philippine corn yield: 60-80 cavans/hectare</small>');
        }

        // Show success toast
        toastr.success(`${cropName} selected!`, 'Crop Selected');
    });

    // Function to update Step 2 based on crop selection
    function updateStep2ForCrop(cropType) {
        // Reset selections
        $('#breed_type').val('');
        $('#corn_type').val('');
        $('#variety_id').val('');
        varietiesData = [];
        $('.breed-selection-box').removeClass('selected');

        // Hide sections with animation
        hideSection($('#variety-section'), 'slide');
        hideSection($('#manual-entry-section'), 'slide');
        hideSection($('#selected-variety-display'), 'fade');
        showSection($('#variety-search-container'), 'fade');
        $('#variety_search').val('');

        // Clear manual entry fields
        $('#manual_variety_name').val('').removeClass('is-invalid');
        $('#manual_manufacturer').val('');
        $('#manual_yield').val('');
        $('#manual_maturity').val('');
        $('#manual_characteristics').val('');

        if (cropType === 'palay') {
            // Show rice breed options with animation
            hideSection($('#corn-breed-section'), 'fade', function() {
                showSection($('#rice-breed-section'), 'fade');
            });
            $('#step2-title').text('Pumili ng Uri ng Palay');
            $('#step2-subtitle').text('Pumili sa pagitan ng Inbred o Hybrid na binhi');
        } else if (cropType === 'corn') {
            // Show corn type options with animation
            hideSection($('#rice-breed-section'), 'fade', function() {
                showSection($('#corn-breed-section'), 'fade');
            });
            $('#step2-title').text('Pumili ng Uri ng Mais');
            $('#step2-subtitle').text('Pumili sa pagitan ng Yellow o White corn');
        }

        $('#breed-selection-hint').html('<i class="bx bx-info-circle me-1"></i>Select a breed type to continue');
    }

    // Function to load varieties from API
    function loadVarieties(cropType, breedType, cornType) {
        const params = new URLSearchParams();

        if (cropType === 'palay') {
            params.append('crop_type', 'rice');
            params.append('breed_type', breedType);
        } else if (cropType === 'corn') {
            params.append('crop_type', 'corn');
            if (cornType) {
                params.append('corn_type', cornType);
            }
        }

        // Show loading state
        $('#variety-list-container').html(`
            <div class="variety-list-loading">
                <i class="bx bx-loader-alt bx-spin" style="font-size: 2rem;"></i>
                <p class="mb-0 mt-2">Loading varieties...</p>
            </div>
        `);
        $('#variety-count span').text('Loading...');

        $.ajax({
            url: '{{ route("knowledgebase.crop-breeds.api.breeds") }}?' + params.toString(),
            type: 'GET',
            success: function(response) {
                if (response.success && response.breeds) {
                    varietiesData = response.breeds;
                    renderVarietyList(varietiesData);
                    $('#variety-count span').text(`${varietiesData.length} varieties available`);
                } else {
                    varietiesData = [];
                    renderVarietyList([]);
                    $('#variety-count span').text('0 varieties available');
                }
            },
            error: function() {
                varietiesData = [];
                renderVarietyList([]);
                $('#variety-count span').text('Error loading varieties');
                toastr.error('Failed to load varieties', 'Error');
            }
        });
    }

    // Function to render variety list
    function renderVarietyList(varieties, searchTerm = '') {
        let html = '';

        if (varieties.length === 0 && !searchTerm) {
            html = `
                <div class="variety-list-empty">
                    <i class="bx bx-package"></i>
                    <p class="mb-0">No varieties found for this selection.</p>
                    <small class="text-secondary">You can add your variety manually below.</small>
                </div>
            `;
        } else if (varieties.length === 0 && searchTerm) {
            html = `
                <div class="variety-list-empty">
                    <i class="bx bx-search-alt"></i>
                    <p class="mb-0">No results for "${escapeHtml(searchTerm)}"</p>
                    <small class="text-secondary">Try a different search term or select "Others".</small>
                </div>
            `;
        } else {
            varieties.forEach(function(breed) {
                const selectedClass = $('#variety_id').val() == breed.id ? 'selected' : '';
                html += `
                    <div class="variety-list-item ${selectedClass}" data-id="${breed.id}" data-name="${escapeHtml(breed.name)}" data-manufacturer="${escapeHtml(breed.manufacturer || '')}" data-yield="${escapeHtml(breed.potentialYield || '')}">
                        <div class="variety-item-info">
                            <div class="variety-item-name">${escapeHtml(breed.name)}</div>
                            <div class="variety-item-meta">
                                ${breed.manufacturer ? `<i class="bx bx-building-house me-1"></i>${escapeHtml(breed.manufacturer)}` : ''}
                                ${breed.potentialYield ? ` &bull; <i class="bx bx-trending-up me-1"></i>${escapeHtml(breed.potentialYield)}` : ''}
                            </div>
                        </div>
                        <div class="variety-item-actions">
                            <button type="button" class="variety-view-btn" data-id="${breed.id}" title="View Details">
                                <i class="bx bx-show"></i>
                            </button>
                            <i class="bx bx-check variety-item-check"></i>
                        </div>
                    </div>
                `;
            });
        }

        // Always add "Others" option at the bottom
        html += `
            <div class="variety-list-item variety-others-option" data-id="others" data-name="Others (Manual Entry)">
                <div class="variety-item-info">
                    <div class="variety-item-name"><i class="bx bx-edit-alt me-1"></i>Others (Enter Manually)</div>
                    <div class="variety-item-meta">Can't find your variety? Enter it manually.</div>
                </div>
                <i class="bx bx-chevron-right variety-item-check" style="opacity: 1; color: #ff9800;"></i>
            </div>
        `;

        $('#variety-list-container').html(html);
    }

    // Function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Function to filter varieties based on search
    function filterVarieties(searchTerm) {
        if (!searchTerm) {
            renderVarietyList(varietiesData);
            return;
        }

        const filtered = varietiesData.filter(function(breed) {
            const search = searchTerm.toLowerCase();
            return (breed.name && breed.name.toLowerCase().includes(search)) ||
                   (breed.manufacturer && breed.manufacturer.toLowerCase().includes(search));
        });

        renderVarietyList(filtered, searchTerm);
    }

    // Function to select a variety
    function selectVariety(id, name, manufacturer, yieldValue) {
        if (id === 'others') {
            // Show manual entry section with animation
            $('#variety_id').val('others');
            hideSection($('#selected-variety-display'), 'fade');
            hideSection($('#variety-search-container'), 'slide', function() {
                showSection($('#manual-entry-section'), 'slide');
                // Focus on variety name field
                setTimeout(function() {
                    $('#manual_variety_name').focus();
                }, 100);
            });
            // Clear manual fields
            $('#manual_variety_name').val('');
            $('#manual_manufacturer').val('');
            $('#manual_yield').val('');
            $('#manual_maturity').val('');
            $('#manual_characteristics').val('');
            toastr.info('Please enter your variety details manually.', 'Manual Entry');
        } else {
            // Select a variety from the list with animation
            $('#variety_id').val(id);
            $('#selected-variety-name').text(name);
            let meta = '';
            if (manufacturer) meta += manufacturer;
            if (yieldValue) meta += (meta ? ' • ' : '') + yieldValue;
            $('#selected-variety-meta').text(meta || 'No additional info');
            hideSection($('#variety-search-container'), 'slide');
            hideSection($('#manual-entry-section'), 'slide');
            showSection($('#selected-variety-display'), 'fade');
            toastr.success(`Variety "${name}" selected!`, 'Variety Selected');
        }
    }

    // Search input handler
    $('#variety_search').on('input', function() {
        const searchTerm = $(this).val().trim();
        if (searchTerm) {
            $('#clear-variety-search').show();
        } else {
            $('#clear-variety-search').hide();
        }
        filterVarieties(searchTerm);
    });

    // Clear search button
    $('#clear-variety-search').on('click', function() {
        $('#variety_search').val('');
        $(this).hide();
        filterVarieties('');
        $('#variety_search').focus();
    });

    // Variety list item click handler (delegated)
    $(document).on('click', '.variety-list-item', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const manufacturer = $(this).data('manufacturer');
        const yieldValue = $(this).data('yield');
        selectVariety(id, name, manufacturer, yieldValue);
    });

    // Change variety button
    $('#change-variety-btn').on('click', function() {
        $('#variety_id').val('');
        hideSection($('#selected-variety-display'), 'fade');
        hideSection($('#manual-entry-section'), 'slide');
        showSection($('#variety-search-container'), 'slide');
        setTimeout(function() {
            $('#variety_search').val('').focus();
        }, 300);
        filterVarieties('');
    });

    // Rice breed selection handler
    $('.breed-selection-box[data-crop="rice"]').on('click', function() {
        const $this = $(this);
        const breedType = $this.data('breed');

        // Remove selected from rice boxes only
        $('.breed-selection-box[data-crop="rice"]').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#breed_type').val(breedType);
        $('#corn_type').val('');

        // Show variety section with animation and load varieties
        showSection($('#variety-section'), 'slide');
        loadVarieties('palay', breedType, null);

        // Update hint text
        const breedName = breedType === 'inbred' ? 'Inbred' : 'Hybrid';
        $('#breed-selection-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected: <strong>${breedName}</strong>`);

        // Show success toast
        toastr.success(`${breedName} rice selected!`, 'Breed Type Selected');
    });

    // Corn type selection handler
    $('.breed-selection-box[data-crop="corn"]').on('click', function() {
        const $this = $(this);
        const cornType = $this.data('corn-type');

        // Remove selected from corn boxes only
        $('.breed-selection-box[data-crop="corn"]').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#corn_type').val(cornType);
        $('#breed_type').val('');

        // Show variety section with animation and load varieties
        showSection($('#variety-section'), 'slide');
        loadVarieties('corn', null, cornType);

        // Update hint text
        const cornName = cornType === 'yellow' ? 'Yellow Corn' : 'White Corn';
        $('#breed-selection-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected: <strong>${cornName}</strong>`);

        // Show success toast
        toastr.success(`${cornName} selected!`, 'Corn Type Selected');
    });

    // Step 3: Planting System Selection Handlers

    // Function to update Step 3 planting system section based on crop selection
    function updateStep3ForCrop(cropType) {
        // Reset planting system selections
        $('#rice_planting_system').val('');
        $('#corn_planting_system').val('');
        $('.planting-system-box').removeClass('selected');

        if (cropType === 'palay') {
            // Show rice planting options with animation
            hideSection($('#corn-planting-section'), 'fade', function() {
                showSection($('#rice-planting-section'), 'fade');
            });
        } else if (cropType === 'corn') {
            // Show corn planting options with animation
            hideSection($('#rice-planting-section'), 'fade', function() {
                showSection($('#corn-planting-section'), 'fade');
            });
        } else {
            // Hide both sections
            hideSection($('#rice-planting-section'), 'fade');
            hideSection($('#corn-planting-section'), 'fade');
        }

        $('#planting-system-hint').html('<i class="bx bx-info-circle me-1"></i>Select a planting system to continue');
    }

    // Function to update Step 18 pest sections based on crop selection
    function updateStep18ForCrop(cropType) {
        // Reset pest selections
        $('.pest-box').removeClass('selected');
        $('#usual_pests').val('');

        if (cropType === 'palay') {
            hideSection($('#corn-pests-section'), 'fade', function() {
                showSection($('#rice-pests-section'), 'fade');
            });
        } else if (cropType === 'corn') {
            hideSection($('#rice-pests-section'), 'fade', function() {
                showSection($('#corn-pests-section'), 'fade');
            });
        } else {
            showSection($('#rice-pests-section'), 'fade');
            showSection($('#corn-pests-section'), 'fade');
        }
    }

    // Rice planting system selection handler
    $('.planting-system-box[data-crop="rice"]').on('click', function() {
        const $this = $(this);
        const system = $this.data('system');

        // Remove selected from rice planting boxes only
        $('.planting-system-box[data-crop="rice"]').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#rice_planting_system').val(system);
        $('#corn_planting_system').val('');

        // Update hint text
        let systemName = '';
        if (system === 'transplanted') {
            systemName = 'Transplanted (Inilipat-tanim)';
        } else if (system === 'direct_wet') {
            systemName = 'Direct Seeding - Wet (Sabog sa Basang Lupa)';
        } else if (system === 'direct_dry') {
            systemName = 'Direct Seeding - Dry (Sabog sa Tuyong Lupa)';
        }
        $('#planting-system-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected: <strong>${systemName}</strong>`);

        // Show success toast
        toastr.success(`${systemName} selected!`, 'Planting System Selected');
    });

    // Corn planting system selection handler
    $('.planting-system-box[data-crop="corn"]').on('click', function() {
        const $this = $(this);
        const system = $this.data('system');

        // Remove selected from corn planting boxes only
        $('.planting-system-box[data-crop="corn"]').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#corn_planting_system').val(system);
        $('#rice_planting_system').val('');

        // Update hint text
        let systemName = '';
        if (system === 'single_row') {
            systemName = 'Single Row (Isahang Hanay)';
        } else if (system === 'double_row') {
            systemName = 'Double Row (Dalawahang Hanay)';
        }
        $('#planting-system-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected: <strong>${systemName}</strong>`);

        // Show success toast
        toastr.success(`${systemName} selected!`, 'Planting System Selected');
    });

    // Step 4: Farm size preset buttons and input handlers
    $('.farm-preset-btn').on('click', function() {
        const size = $(this).data('size');
        const unit = $(this).data('unit');

        // Update input values
        $('#farm_size_input').val(size);
        $('#farm_unit_select').val(unit);

        // Update hidden fields
        $('#farm_size').val(size);
        $('#farm_unit').val(unit);

        // Update button states
        $('.farm-preset-btn').removeClass('active');
        $(this).addClass('active');
    });

    // Step 3: Farm size input change
    $('#farm_size_input').on('input change', function() {
        const size = $(this).val();
        $('#farm_size').val(size);
        $('.farm-preset-btn').removeClass('active');
    });

    $('#farm_unit_select').on('change', function() {
        const unit = $(this).val();
        $('#farm_unit').val(unit);
    });

    // Step 5: Cascading Location Dropdowns
    // Philippine municipalities data by province
    const municipalitiesData = {
        // Ilocos Region
        'Ilocos Norte': ['Laoag City', 'Batac City', 'Adams', 'Bacarra', 'Badoc', 'Bangui', 'Banna', 'Burgos', 'Carasi', 'Currimao', 'Dingras', 'Dumalneg', 'Marcos', 'Nueva Era', 'Pagudpud', 'Paoay', 'Pasuquin', 'Piddig', 'Pinili', 'San Nicolas', 'Sarrat', 'Solsona', 'Vintar'],
        'Ilocos Sur': ['Vigan City', 'Candon City', 'Bantay', 'Cabugao', 'Caoayan', 'Cervantes', 'Galimuyod', 'Gregorio del Pilar', 'Magsingal', 'Nagbukel', 'Narvacan', 'San Esteban', 'San Ildefonso', 'San Juan', 'San Vicente', 'Santa', 'Santa Catalina', 'Santa Cruz', 'Santa Lucia', 'Santa Maria', 'Santiago', 'Santo Domingo', 'Sigay', 'Sinait', 'Sugpon', 'Suyo', 'Tagudin'],
        'La Union': ['San Fernando City', 'Agoo', 'Aringay', 'Bacnotan', 'Bagulin', 'Balaoan', 'Bangar', 'Bauang', 'Burgos', 'Caba', 'Luna', 'Naguilian', 'Pugo', 'Rosario', 'San Gabriel', 'San Juan', 'Santo Tomas', 'Santol', 'Sudipen', 'Tubao'],
        'Pangasinan': ['Dagupan City', 'Alaminos City', 'San Carlos City', 'Urdaneta City', 'Agno', 'Aguilar', 'Alcala', 'Anda', 'Asingan', 'Balungao', 'Bani', 'Basista', 'Bautista', 'Bayambang', 'Binalonan', 'Binmaley', 'Bolinao', 'Bugallon', 'Burgos', 'Calasiao', 'Dasol', 'Infanta', 'Labrador', 'Laoac', 'Lingayen', 'Mabini', 'Malasiqui', 'Manaoag', 'Mangaldan', 'Mangatarem', 'Mapandan', 'Natividad', 'Pozorrubio', 'Rosales', 'San Fabian', 'San Jacinto', 'San Manuel', 'San Nicolas', 'San Quintin', 'Santa Barbara', 'Santa Maria', 'Santo Tomas', 'Sison', 'Sual', 'Tayug', 'Umingan', 'Urbiztondo', 'Villasis'],
        // Cagayan Valley
        'Batanes': ['Basco', 'Itbayat', 'Ivana', 'Mahatao', 'Sabtang', 'Uyugan'],
        'Cagayan': ['Tuguegarao City', 'Abulug', 'Alcala', 'Allacapan', 'Amulung', 'Aparri', 'Baggao', 'Ballesteros', 'Buguey', 'Calayan', 'Camalaniugan', 'Claveria', 'Enrile', 'Gattaran', 'Gonzaga', 'Iguig', 'Lal-lo', 'Lasam', 'Pamplona', 'Peñablanca', 'Piat', 'Rizal', 'Sanchez-Mira', 'Santa Ana', 'Santa Praxedes', 'Santa Teresita', 'Santo Niño', 'Solana', 'Tuao'],
        'Isabela': ['Ilagan City', 'Cauayan City', 'Santiago City', 'Alicia', 'Angadanan', 'Aurora', 'Benito Soliven', 'Burgos', 'Cabagan', 'Cabatuan', 'Cordon', 'Delfin Albano', 'Dinapigue', 'Divilacan', 'Echague', 'Gamu', 'Jones', 'Luna', 'Maconacon', 'Mallig', 'Naguilian', 'Palanan', 'Quezon', 'Quirino', 'Ramon', 'Reina Mercedes', 'Roxas', 'San Agustin', 'San Guillermo', 'San Isidro', 'San Manuel', 'San Mariano', 'San Mateo', 'San Pablo', 'Santa Maria', 'Santo Tomas', 'Tumauini'],
        'Nueva Vizcaya': ['Bayombong', 'Ambaguio', 'Aritao', 'Bagabag', 'Bambang', 'Diadi', 'Dupax del Norte', 'Dupax del Sur', 'Kasibu', 'Kayapa', 'Quezon', 'Santa Fe', 'Solano', 'Villaverde'],
        'Quirino': ['Cabarroguis', 'Aglipay', 'Diffun', 'Maddela', 'Nagtipunan', 'Saguday'],
        // Central Luzon
        'Aurora': ['Baler', 'Casiguran', 'Dilasag', 'Dinalungan', 'Dingalan', 'Dipaculao', 'Maria Aurora', 'San Luis'],
        'Bataan': ['Balanga City', 'Abucay', 'Bagac', 'Dinalupihan', 'Hermosa', 'Limay', 'Mariveles', 'Morong', 'Orani', 'Orion', 'Pilar', 'Samal'],
        'Bulacan': ['Malolos City', 'Meycauayan City', 'San Jose del Monte City', 'Angat', 'Balagtas', 'Baliuag', 'Bocaue', 'Bulakan', 'Bustos', 'Calumpit', 'Doña Remedios Trinidad', 'Guiguinto', 'Hagonoy', 'Marilao', 'Norzagaray', 'Obando', 'Pandi', 'Paombong', 'Plaridel', 'Pulilan', 'San Ildefonso', 'San Miguel', 'San Rafael', 'Santa Maria'],
        'Nueva Ecija': ['Cabanatuan City', 'Gapan City', 'Palayan City', 'San Jose City', 'Science City of Muñoz', 'Aliaga', 'Bongabon', 'Cabiao', 'Carranglan', 'Cuyapo', 'Gabaldon', 'General Mamerto Natividad', 'General Tinio', 'Guimba', 'Jaen', 'Laur', 'Licab', 'Llanera', 'Lupao', 'Nampicuan', 'Pantabangan', 'Peñaranda', 'Quezon', 'Rizal', 'San Antonio', 'San Isidro', 'San Leonardo', 'Santa Rosa', 'Santo Domingo', 'Talavera', 'Talugtug', 'Zaragoza'],
        'Pampanga': ['San Fernando City', 'Angeles City', 'Mabalacat City', 'Apalit', 'Arayat', 'Bacolor', 'Candaba', 'Floridablanca', 'Guagua', 'Lubao', 'Macabebe', 'Magalang', 'Masantol', 'Mexico', 'Minalin', 'Porac', 'San Luis', 'San Simon', 'Santa Ana', 'Santa Rita', 'Santo Tomas', 'Sasmuan'],
        'Tarlac': ['Tarlac City', 'Anao', 'Bamban', 'Camiling', 'Capas', 'Concepcion', 'Gerona', 'La Paz', 'Mayantoc', 'Moncada', 'Paniqui', 'Pura', 'Ramos', 'San Clemente', 'San Jose', 'San Manuel', 'Santa Ignacia', 'Victoria'],
        'Zambales': ['Olongapo City', 'Botolan', 'Cabangan', 'Candelaria', 'Castillejos', 'Iba', 'Masinloc', 'Palauig', 'San Antonio', 'San Felipe', 'San Marcelino', 'San Narciso', 'Santa Cruz', 'Subic'],
        // CALABARZON
        'Batangas': ['Batangas City', 'Lipa City', 'Tanauan City', 'Agoncillo', 'Alitagtag', 'Balayan', 'Balete', 'Bauan', 'Calaca', 'Calatagan', 'Cuenca', 'Ibaan', 'Laurel', 'Lemery', 'Lian', 'Lobo', 'Mabini', 'Malvar', 'Mataas na Kahoy', 'Nasugbu', 'Padre Garcia', 'Rosario', 'San Jose', 'San Juan', 'San Luis', 'San Nicolas', 'San Pascual', 'Santa Teresita', 'Santo Tomas', 'Taal', 'Taysan', 'Tingloy', 'Tuy'],
        'Cavite': ['Cavite City', 'Tagaytay City', 'Trece Martires City', 'Bacoor City', 'Dasmariñas City', 'Imus City', 'General Trias City', 'Alfonso', 'Amadeo', 'Carmona', 'General Mariano Alvarez', 'Indang', 'Kawit', 'Magallanes', 'Maragondon', 'Mendez', 'Naic', 'Noveleta', 'Rosario', 'Silang', 'Tanza', 'Ternate'],
        'Laguna': ['Santa Rosa City', 'Biñan City', 'Calamba City', 'San Pedro City', 'Cabuyao City', 'Alaminos', 'Bay', 'Calauan', 'Cavinti', 'Famy', 'Kalayaan', 'Liliw', 'Los Baños', 'Luisiana', 'Lumban', 'Mabitac', 'Magdalena', 'Majayjay', 'Nagcarlan', 'Paete', 'Pagsanjan', 'Pakil', 'Pangil', 'Pila', 'Rizal', 'San Pablo City', 'Santa Cruz', 'Santa Maria', 'Siniloan', 'Victoria'],
        'Quezon': ['Lucena City', 'Tayabas City', 'Agdangan', 'Alabat', 'Atimonan', 'Buenavista', 'Burdeos', 'Calauag', 'Candelaria', 'Catanauan', 'Dolores', 'General Luna', 'General Nakar', 'Guinayangan', 'Gumaca', 'Infanta', 'Jomalig', 'Lopez', 'Lucban', 'Macalelon', 'Mauban', 'Mulanay', 'Padre Burgos', 'Pagbilao', 'Panukulan', 'Patnanungan', 'Perez', 'Pitogo', 'Plaridel', 'Polillo', 'Quezon', 'Real', 'Sampaloc', 'San Andres', 'San Antonio', 'San Francisco', 'San Narciso', 'Sariaya', 'Tagkawayan', 'Tiaong', 'Unisan'],
        'Rizal': ['Antipolo City', 'Angono', 'Baras', 'Binangonan', 'Cainta', 'Cardona', 'Jalajala', 'Morong', 'Pililla', 'Rodriguez', 'San Mateo', 'Tanay', 'Taytay', 'Teresa'],
        // MIMAROPA
        'Marinduque': ['Boac', 'Buenavista', 'Gasan', 'Mogpog', 'Santa Cruz', 'Torrijos'],
        'Occidental Mindoro': ['Mamburao', 'Abra de Ilog', 'Calintaan', 'Looc', 'Lubang', 'Magsaysay', 'Paluan', 'Rizal', 'Sablayan', 'San Jose', 'Santa Cruz'],
        'Oriental Mindoro': ['Calapan City', 'Baco', 'Bansud', 'Bongabong', 'Bulalacao', 'Gloria', 'Mansalay', 'Naujan', 'Pinamalayan', 'Pola', 'Puerto Galera', 'Roxas', 'San Teodoro', 'Socorro', 'Victoria'],
        'Palawan': ['Puerto Princesa City', 'Aborlan', 'Agutaya', 'Araceli', 'Balabac', 'Bataraza', 'Brooke\'s Point', 'Busuanga', 'Cagayancillo', 'Coron', 'Culion', 'Cuyo', 'Dumaran', 'El Nido', 'Kalayaan', 'Linapacan', 'Magsaysay', 'Narra', 'Quezon', 'Rizal', 'Roxas', 'San Vicente', 'Sofronio Española', 'Taytay'],
        'Romblon': ['Romblon', 'Alcantara', 'Banton', 'Cajidiocan', 'Calatrava', 'Concepcion', 'Corcuera', 'Ferrol', 'Looc', 'Magdiwang', 'Odiongan', 'San Agustin', 'San Andres', 'San Fernando', 'San Jose', 'Santa Fe', 'Santa Maria'],
        // Bicol Region
        'Albay': ['Legazpi City', 'Ligao City', 'Tabaco City', 'Bacacay', 'Camalig', 'Daraga', 'Guinobatan', 'Jovellar', 'Libon', 'Malilipot', 'Malinao', 'Manito', 'Oas', 'Pio Duran', 'Polangui', 'Rapu-Rapu', 'Santo Domingo', 'Tiwi'],
        'Camarines Norte': ['Daet', 'Basud', 'Capalonga', 'Jose Panganiban', 'Labo', 'Mercedes', 'Paracale', 'San Lorenzo Ruiz', 'San Vicente', 'Santa Elena', 'Talisay', 'Vinzons'],
        'Camarines Sur': ['Naga City', 'Iriga City', 'Baao', 'Balatan', 'Bato', 'Bombon', 'Buhi', 'Bula', 'Cabusao', 'Calabanga', 'Camaligan', 'Canaman', 'Caramoan', 'Del Gallego', 'Gainza', 'Garchitorena', 'Goa', 'Lagonoy', 'Libmanan', 'Lupi', 'Magarao', 'Milaor', 'Minalabac', 'Nabua', 'Ocampo', 'Pamplona', 'Pasacao', 'Pili', 'Presentacion', 'Ragay', 'Sagñay', 'San Fernando', 'San Jose', 'Sipocot', 'Siruma', 'Tigaon', 'Tinambac'],
        'Catanduanes': ['Virac', 'Bagamanoc', 'Baras', 'Bato', 'Caramoran', 'Gigmoto', 'Pandan', 'Panganiban', 'San Andres', 'San Miguel', 'Viga'],
        'Masbate': ['Masbate City', 'Aroroy', 'Baleno', 'Balud', 'Batuan', 'Cataingan', 'Cawayan', 'Claveria', 'Dimasalang', 'Esperanza', 'Mandaon', 'Milagros', 'Mobo', 'Monreal', 'Palanas', 'Pio V. Corpuz', 'Placer', 'San Fernando', 'San Jacinto', 'San Pascual', 'Uson'],
        'Sorsogon': ['Sorsogon City', 'Barcelona', 'Bulan', 'Bulusan', 'Casiguran', 'Castilla', 'Donsol', 'Gubat', 'Irosin', 'Juban', 'Magallanes', 'Matnog', 'Pilar', 'Prieto Diaz', 'Santa Magdalena'],
        // Western Visayas
        'Aklan': ['Kalibo', 'Altavas', 'Balete', 'Banga', 'Batan', 'Buruanga', 'Ibajay', 'Lezo', 'Libacao', 'Madalag', 'Makato', 'Malay', 'Malinao', 'Nabas', 'New Washington', 'Numancia', 'Tangalan'],
        'Antique': ['San Jose de Buenavista', 'Anini-y', 'Barbaza', 'Belison', 'Bugasong', 'Caluya', 'Culasi', 'Hamtic', 'Laua-an', 'Libertad', 'Pandan', 'Patnongon', 'San Remigio', 'Sebaste', 'Sibalom', 'Tibiao', 'Tobias Fornier', 'Valderrama'],
        'Capiz': ['Roxas City', 'Cuartero', 'Dao', 'Dumalag', 'Dumarao', 'Ivisan', 'Jamindan', 'Ma-ayon', 'Mambusao', 'Panay', 'Panitan', 'Pilar', 'Pontevedra', 'President Roxas', 'Sapian', 'Sigma', 'Tapaz'],
        'Guimaras': ['Jordan', 'Buenavista', 'Nueva Valencia', 'San Lorenzo', 'Sibunag'],
        'Iloilo': ['Iloilo City', 'Passi City', 'Ajuy', 'Alimodian', 'Anilao', 'Badiangan', 'Balasan', 'Banate', 'Barotac Nuevo', 'Barotac Viejo', 'Batad', 'Bingawan', 'Cabatuan', 'Calinog', 'Carles', 'Concepcion', 'Dingle', 'Dueñas', 'Dumangas', 'Estancia', 'Guimbal', 'Igbaras', 'Janiuay', 'Lambunao', 'Leganes', 'Lemery', 'Leon', 'Maasin', 'Miagao', 'Mina', 'New Lucena', 'Oton', 'Pavia', 'Pototan', 'San Dionisio', 'San Enrique', 'San Joaquin', 'San Miguel', 'San Rafael', 'Santa Barbara', 'Sara', 'Tigbauan', 'Tubungan', 'Zarraga'],
        'Negros Occidental': ['Bacolod City', 'Bago City', 'Cadiz City', 'Escalante City', 'Himamaylan City', 'Kabankalan City', 'La Carlota City', 'Sagay City', 'San Carlos City', 'Silay City', 'Sipalay City', 'Talisay City', 'Victorias City', 'Binalbagan', 'Calatrava', 'Candoni', 'Cauayan', 'Enrique B. Magalona', 'Hinigaran', 'Hinoba-an', 'Ilog', 'Isabela', 'La Castellana', 'Manapla', 'Moises Padilla', 'Murcia', 'Pontevedra', 'Pulupandan', 'Salvador Benedicto', 'San Enrique', 'Toboso', 'Valladolid'],
        // Central Visayas
        'Bohol': ['Tagbilaran City', 'Alburquerque', 'Alicia', 'Anda', 'Antequera', 'Baclayon', 'Balilihan', 'Batuan', 'Bien Unido', 'Bilar', 'Buenavista', 'Calape', 'Candijay', 'Carmen', 'Catigbian', 'Clarin', 'Corella', 'Cortes', 'Dagohoy', 'Danao', 'Dauis', 'Dimiao', 'Duero', 'Garcia Hernandez', 'Getafe', 'Guindulman', 'Inabanga', 'Jagna', 'Lila', 'Loay', 'Loboc', 'Loon', 'Mabini', 'Maribojoc', 'Panglao', 'Pilar', 'President Carlos P. Garcia', 'Sagbayan', 'San Isidro', 'San Miguel', 'Sevilla', 'Sierra Bullones', 'Sikatuna', 'Talibon', 'Trinidad', 'Tubigon', 'Ubay', 'Valencia'],
        'Cebu': ['Cebu City', 'Lapu-Lapu City', 'Mandaue City', 'Danao City', 'Toledo City', 'Talisay City', 'Naga City', 'Carcar City', 'Bogo City', 'Alcantara', 'Alcoy', 'Alegria', 'Aloguinsan', 'Argao', 'Asturias', 'Badian', 'Balamban', 'Bantayan', 'Barili', 'Boljoon', 'Borbon', 'Carmen', 'Catmon', 'Compostela', 'Consolacion', 'Cordova', 'Daanbantayan', 'Dalaguete', 'Dumanjug', 'Ginatilan', 'Liloan', 'Madridejos', 'Malabuyoc', 'Medellin', 'Minglanilla', 'Moalboal', 'Oslob', 'Pilar', 'Pinamungajan', 'Poro', 'Ronda', 'Samboan', 'San Fernando', 'San Francisco', 'San Remigio', 'Santa Fe', 'Santander', 'Sibonga', 'Sogod', 'Tabogon', 'Tabuelan', 'Tuburan', 'Tudela'],
        'Negros Oriental': ['Dumaguete City', 'Bais City', 'Bayawan City', 'Canlaon City', 'Guihulngan City', 'Tanjay City', 'Amlan', 'Ayungon', 'Bacong', 'Basay', 'Bindoy', 'Dauin', 'Jimalalud', 'La Libertad', 'Mabinay', 'Manjuyod', 'Pamplona', 'San Jose', 'Santa Catalina', 'Siaton', 'Sibulan', 'Tayasan', 'Valencia', 'Vallehermoso', 'Zamboanguita'],
        'Siquijor': ['Siquijor', 'Enrique Villanueva', 'Larena', 'Lazi', 'Maria', 'San Juan'],
        // Eastern Visayas
        'Biliran': ['Naval', 'Almeria', 'Biliran', 'Cabucgayan', 'Caibiran', 'Culaba', 'Kawayan', 'Maripipi'],
        'Eastern Samar': ['Borongan City', 'Arteche', 'Balangiga', 'Balangkayan', 'Can-avid', 'Dolores', 'General MacArthur', 'Giporlos', 'Guiuan', 'Hernani', 'Jipapad', 'Lawaan', 'Llorente', 'Maslog', 'Maydolong', 'Mercedes', 'Oras', 'Quinapondan', 'Salcedo', 'San Julian', 'San Policarpo', 'Sulat', 'Taft'],
        'Leyte': ['Tacloban City', 'Ormoc City', 'Abuyog', 'Alangalang', 'Albuera', 'Babatngon', 'Barugo', 'Bato', 'Baybay City', 'Burauen', 'Calubian', 'Capoocan', 'Carigara', 'Dagami', 'Dulag', 'Hilongos', 'Hindang', 'Inopacan', 'Isabel', 'Jaro', 'Javier', 'Julita', 'Kananga', 'La Paz', 'Leyte', 'MacArthur', 'Mahaplag', 'Matag-ob', 'Matalom', 'Mayorga', 'Merida', 'Palo', 'Palompon', 'Pastrana', 'San Isidro', 'San Miguel', 'Santa Fe', 'Tabango', 'Tabontabon', 'Tanauan', 'Tolosa', 'Tunga', 'Villaba'],
        'Northern Samar': ['Catarman', 'Allen', 'Biri', 'Bobon', 'Capul', 'Catubig', 'Gamay', 'Laoang', 'Lapinig', 'Las Navas', 'Lavezares', 'Lope de Vega', 'Mapanas', 'Mondragon', 'Palapag', 'Pambujan', 'Rosario', 'San Antonio', 'San Isidro', 'San Jose', 'San Roque', 'San Vicente', 'Silvino Lobos', 'Victoria'],
        'Samar': ['Catbalogan City', 'Calbayog City', 'Almagro', 'Basey', 'Calbiga', 'Daram', 'Gandara', 'Hinabangan', 'Jiabong', 'Marabut', 'Matuguinao', 'Motiong', 'Pagsanghan', 'Paranas', 'Pinabacdao', 'San Jorge', 'San Jose de Buan', 'San Sebastian', 'Santa Margarita', 'Santa Rita', 'Santo Niño', 'Tagapul-an', 'Talalora', 'Tarangnan', 'Villareal', 'Zumarraga'],
        'Southern Leyte': ['Maasin City', 'Anahawan', 'Bontoc', 'Hinunangan', 'Hinundayan', 'Libagon', 'Liloan', 'Limasawa', 'Macrohon', 'Malitbog', 'Padre Burgos', 'Pintuyan', 'Saint Bernard', 'San Francisco', 'San Juan', 'San Ricardo', 'Silago', 'Sogod', 'Tomas Oppus'],
        // Zamboanga Peninsula
        'Zamboanga del Norte': ['Dipolog City', 'Dapitan City', 'Bacungan', 'Baliguian', 'Godod', 'Gutalac', 'Jose Dalman', 'Kalawit', 'Katipunan', 'La Libertad', 'Labason', 'Leon B. Postigo', 'Liloy', 'Manukan', 'Mutia', 'Piñan', 'Polanco', 'Rizal', 'Roxas', 'Salug', 'Sergio Osmeña Sr.', 'Siayan', 'Sibuco', 'Sibutad', 'Sindangan', 'Siocon', 'Sirawai', 'Tampilisan'],
        'Zamboanga del Sur': ['Pagadian City', 'Zamboanga City', 'Aurora', 'Bayog', 'Dimataling', 'Dinas', 'Dumalinao', 'Dumingag', 'Guipos', 'Josefina', 'Kumalarang', 'Labangan', 'Lakewood', 'Lapuyan', 'Mahayag', 'Margosatubig', 'Midsalip', 'Molave', 'Pitogo', 'Ramon Magsaysay', 'San Miguel', 'San Pablo', 'Sominot', 'Tabina', 'Tambulig', 'Tigbao', 'Tukuran', 'Vincenzo A. Sagun'],
        'Zamboanga Sibugay': ['Ipil', 'Alicia', 'Buug', 'Diplahan', 'Imelda', 'Kabasalan', 'Mabuhay', 'Malangas', 'Naga', 'Olutanga', 'Payao', 'Roseller Lim', 'Siay', 'Talusan', 'Titay', 'Tungawan'],
        // Northern Mindanao
        'Bukidnon': ['Malaybalay City', 'Valencia City', 'Baungon', 'Cabanglasan', 'Damulog', 'Dangcagan', 'Don Carlos', 'Impasugong', 'Kadingilan', 'Kalilangan', 'Kibawe', 'Kitaotao', 'Lantapan', 'Libona', 'Malitbog', 'Manolo Fortich', 'Maramag', 'Pangantucan', 'Quezon', 'San Fernando', 'Sumilao', 'Talakag'],
        'Camiguin': ['Mambajao', 'Catarman', 'Guinsiliban', 'Mahinog', 'Sagay'],
        'Lanao del Norte': ['Iligan City', 'Bacolod', 'Baloi', 'Baroy', 'Kapatagan', 'Kauswagan', 'Kolambugan', 'Lala', 'Linamon', 'Magsaysay', 'Maigo', 'Matungao', 'Munai', 'Nunungan', 'Pantao Ragat', 'Pantar', 'Poona Piagapo', 'Salvador', 'Sapad', 'Sultan Naga Dimaporo', 'Tagoloan', 'Tangcal', 'Tubod'],
        'Misamis Occidental': ['Oroquieta City', 'Ozamiz City', 'Tangub City', 'Aloran', 'Baliangao', 'Bonifacio', 'Calamba', 'Clarin', 'Concepcion', 'Don Victoriano Chiongbian', 'Jimenez', 'Lopez Jaena', 'Panaon', 'Plaridel', 'Sapang Dalaga', 'Sinacaban', 'Tudela'],
        'Misamis Oriental': ['Cagayan de Oro City', 'Gingoog City', 'El Salvador City', 'Alubijid', 'Balingasag', 'Balingoan', 'Binuangan', 'Claveria', 'Gitagum', 'Initao', 'Jasaan', 'Kinoguitan', 'Lagonglong', 'Laguindingan', 'Libertad', 'Lugait', 'Magsaysay', 'Manticao', 'Medina', 'Naawan', 'Opol', 'Salay', 'Sugbongcogon', 'Tagoloan', 'Talisayan', 'Villanueva'],
        // Davao Region
        'Davao de Oro': ['Nabunturan', 'Compostela', 'Laak', 'Mabini', 'Maco', 'Maragusan', 'Mawab', 'Monkayo', 'Montevista', 'New Bataan', 'Pantukan'],
        'Davao del Norte': ['Tagum City', 'Panabo City', 'Island Garden City of Samal', 'Asuncion', 'Braulio E. Dujali', 'Carmen', 'Kapalong', 'New Corella', 'San Isidro', 'Santo Tomas', 'Talaingod'],
        'Davao del Sur': ['Davao City', 'Digos City', 'Bansalan', 'Don Marcelino', 'Hagonoy', 'Jose Abad Santos', 'Kiblawan', 'Magsaysay', 'Malalag', 'Malita', 'Matanao', 'Padada', 'Santa Cruz', 'Sulop'],
        'Davao Occidental': ['Malita', 'Don Marcelino', 'Jose Abad Santos', 'Santa Maria', 'Sarangani'],
        'Davao Oriental': ['Mati City', 'Baganga', 'Banaybanay', 'Boston', 'Caraga', 'Cateel', 'Governor Generoso', 'Lupon', 'Manay', 'San Isidro', 'Tarragona'],
        // SOCCSKSARGEN
        'Cotabato': ['Kidapawan City', 'Alamada', 'Aleosan', 'Antipas', 'Arakan', 'Banisilan', 'Carmen', 'Kabacan', 'Libungan', 'M\'lang', 'Magpet', 'Makilala', 'Matalam', 'Midsayap', 'Pigcawayan', 'Pikit', 'President Roxas', 'Tulunan'],
        'Sarangani': ['Alabel', 'Glan', 'Kiamba', 'Maasim', 'Maitum', 'Malapatan', 'Malungon'],
        'South Cotabato': ['Koronadal City', 'General Santos City', 'Banga', 'Lake Sebu', 'Norala', 'Polomolok', 'Santo Niño', 'Surallah', 'T\'boli', 'Tampakan', 'Tantangan', 'Tupi'],
        'Sultan Kudarat': ['Isulan', 'Tacurong City', 'Bagumbayan', 'Columbio', 'Esperanza', 'Kalamansig', 'Lambayong', 'Lebak', 'Lutayan', 'Palimbang', 'President Quirino', 'Senator Ninoy Aquino'],
        // Caraga
        'Agusan del Norte': ['Butuan City', 'Cabadbaran City', 'Buenavista', 'Carmen', 'Jabonga', 'Kitcharao', 'Las Nieves', 'Magallanes', 'Nasipit', 'Remedios T. Romualdez', 'Santiago', 'Tubay'],
        'Agusan del Sur': ['Bayugan City', 'Bunawan', 'Esperanza', 'La Paz', 'Loreto', 'Prosperidad', 'Rosario', 'San Francisco', 'San Luis', 'Santa Josefa', 'Sibagat', 'Talacogon', 'Trento', 'Veruela'],
        'Dinagat Islands': ['San Jose', 'Basilisa', 'Cagdianao', 'Dinagat', 'Libjo', 'Loreto', 'Tubajon'],
        'Surigao del Norte': ['Surigao City', 'Alegria', 'Bacuag', 'Burgos', 'Claver', 'Dapa', 'Del Carmen', 'General Luna', 'Gigaquit', 'Mainit', 'Malimono', 'Pilar', 'Placer', 'San Benito', 'San Francisco', 'San Isidro', 'Santa Monica', 'Sison', 'Socorro', 'Tagana-an', 'Tubod'],
        'Surigao del Sur': ['Tandag City', 'Bislig City', 'Barobo', 'Bayabas', 'Cagwait', 'Cantilan', 'Carmen', 'Carrascal', 'Cortes', 'Hinatuan', 'Lanuza', 'Lianga', 'Lingig', 'Madrid', 'Marihatag', 'San Agustin', 'San Miguel', 'Tagbina', 'Tago'],
        // BARMM
        'Basilan': ['Isabela City', 'Lamitan City', 'Akbar', 'Al-Barka', 'Hadji Mohammad Ajul', 'Hadji Muhtamad', 'Lantawan', 'Maluso', 'Sumisip', 'Tabuan-Lasa', 'Tipo-Tipo', 'Tuburan', 'Ungkaya Pukan'],
        'Lanao del Sur': ['Marawi City', 'Bacolod-Kalawi', 'Balabagan', 'Balindong', 'Bayang', 'Binidayan', 'Buadiposo-Buntong', 'Bubong', 'Bumbaran', 'Butig', 'Calanogas', 'Ditsaan-Ramain', 'Ganassi', 'Kapai', 'Kapatagan', 'Lumba-Bayabao', 'Lumbaca-Unayan', 'Lumbatan', 'Lumbayanague', 'Madalum', 'Madamba', 'Maguing', 'Malabang', 'Marantao', 'Marogong', 'Masiu', 'Mulondo', 'Pagayawan', 'Piagapo', 'Picong', 'Poona Bayabao', 'Pualas', 'Saguiaran', 'Sultan Dumalondong', 'Tagoloan II', 'Tamparan', 'Taraka', 'Tubaran', 'Tugaya', 'Wao'],
        'Maguindanao del Norte': ['Datu Odin Sinsuat', 'Buldon', 'Datu Blah T. Sinsuat', 'Kabuntalan', 'Matanog', 'Northern Kabuntalan', 'Parang', 'Sultan Kudarat', 'Sultan Mastura', 'Upi'],
        'Maguindanao del Sur': ['Buluan', 'Cotabato City', 'Ampatuan', 'Datu Abdullah Sangki', 'Datu Anggal Midtimbang', 'Datu Hoffer Ampatuan', 'Datu Montawal', 'Datu Paglas', 'Datu Piang', 'Datu Salibo', 'Datu Saudi-Ampatuan', 'Datu Unsay', 'General Salipada K. Pendatun', 'Guindulungan', 'Mamasapano', 'Mangudadatu', 'Pagalungan', 'Paglat', 'Pandag', 'Rajah Buayan', 'Shariff Aguak', 'Shariff Saydona Mustapha', 'South Upi', 'Sultan sa Barongis', 'Talayan', 'Talitay'],
        'Sulu': ['Jolo', 'Hadji Panglima Tahil', 'Indanan', 'Kalingalan Caluang', 'Lugus', 'Luuk', 'Maimbung', 'Old Panamao', 'Omar', 'Pandami', 'Panglima Estino', 'Pangutaran', 'Parang', 'Pata', 'Patikul', 'Siasi', 'Talipao', 'Tapul', 'Tongkil'],
        'Tawi-Tawi': ['Bongao', 'Languyan', 'Mapun', 'Panglima Sugala', 'Sapa-Sapa', 'Sibutu', 'Simunul', 'Sitangkai', 'South Ubian', 'Tandubas', 'Turtle Islands']
    };

    // Province dropdown change handler
    $('#province_select').on('change', function() {
        const province = $(this).val();
        const $municipalitySelect = $('#municipality_select');
        const $provinceWrapper = $('#province-wrapper');
        const $municipalityWrapper = $('#municipality-wrapper');

        // Update hidden field
        $('#province').val(province);

        // Reset municipality
        $municipalitySelect.html('<option value="">-- Pumili ng Munisipalidad/Lungsod --</option>');

        if (province) {
            // Mark province as completed
            $provinceWrapper.removeClass('active-field').addClass('completed-field');
            $municipalityWrapper.removeClass('disabled-field').addClass('active-field');
            $municipalitySelect.prop('disabled', false);

            // Populate municipalities
            if (municipalitiesData[province]) {
                municipalitiesData[province].forEach(function(muni) {
                    $municipalitySelect.append(`<option value="${muni}">${muni}</option>`);
                });
            }

            // Show success toast
            toastr.success(`Probinsya: ${province}`, 'Napili na');
        } else {
            // Reset all
            $provinceWrapper.addClass('active-field').removeClass('completed-field');
            $municipalityWrapper.removeClass('active-field').addClass('disabled-field');
            $municipalitySelect.prop('disabled', true);
        }

        // Reset municipality hidden field
        $('#municipality').val('');
    });

    // Municipality dropdown change handler
    $('#municipality_select').on('change', function() {
        const municipality = $(this).val();
        const $municipalityWrapper = $('#municipality-wrapper');

        // Update hidden field
        $('#municipality').val(municipality);

        if (municipality) {
            // Mark municipality as completed
            $municipalityWrapper.removeClass('active-field').addClass('completed-field');

            // Show success toast
            toastr.success(`Munisipalidad: ${municipality}`, 'Napili na');
        } else {
            // Reset municipality section
            $municipalityWrapper.addClass('active-field').removeClass('completed-field');
        }
    });

    // Step 5: Season selection
    $('.season-selection-box').on('click', function() {
        const $this = $(this);
        const season = $this.data('season');

        // Remove selected from all season boxes
        $('.season-selection-box').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#cropping_season').val(season);

        // Update hint text
        const seasonNames = {
            'wet': 'Wet Season (Tag-ulan)',
            'dry': 'Dry Season (Tag-init)',
            'transition': 'Transition (Pagitan ng Season)'
        };
        $('#season-selection-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Napili: <strong>${seasonNames[season]}</strong>`);

        // Show success toast
        toastr.success(`${seasonNames[season]} ang napili!`, 'Napili na');

        // Update the selected season display in Step 6
        const seasonDisplayNames = {
            'wet': 'Wet Season',
            'dry': 'Dry Season',
            'transition': 'Transition Season'
        };
        $('#selected-season-display').text(seasonDisplayNames[season] || 'season');
        $('#neighbor-season-display').text(seasonDisplayNames[season] || 'season');
    });

    // Step 7: Yield History selection
    $('.yield-answer-box').on('click', function() {
        const $this = $(this);
        const answer = $this.data('answer');

        // Remove selected from all answer boxes
        $('.yield-answer-box').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#has_low_yield_history').val(answer);

        // Show/hide details section based on answer
        if (answer === 'yes') {
            $('#yield-details-section').removeClass('d-none').hide().slideDown(300);
            $('#yield-history-hint').html('<i class="bx bx-info-circle me-1"></i>Magbigay ng detalye tungkol sa yield history mo');
        } else {
            $('#yield-details-section').slideUp(300, function() {
                $(this).addClass('d-none');
            });
            // Clear the reason selections when "No" or "First Time" is selected
            $('#low_yield_reasons').val('');
            $('.reason-box').removeClass('selected');
            if (answer === 'first_time') {
                $('#yield-history-hint').html('<i class="bx bx-check-circle text-success me-1"></i>First time mo magtanim! Good luck sa cropping mo!');
            } else {
                $('#yield-history-hint').html('<i class="bx bx-check-circle text-success me-1"></i>Maganda! Walang history ng mababang ani.');
            }
        }

        // Show toast
        const answerTexts = { 'yes': 'Oo', 'no': 'Hindi', 'first_time': 'First Time Ko' };
        toastr.success(`Napili: ${answerTexts[answer]}`, 'Napili na');
    });

    // Step 7: Reason boxes (multi-select)
    $('.reason-box').on('click', function() {
        const $this = $(this);
        const reason = $this.data('reason');

        // Toggle selected state
        $this.toggleClass('selected');

        // Update hidden input with all selected reasons
        updateReasonSelections();

        // Show toast
        const reasonLabels = {
            'kulang_tubig': 'Kulang Tubig',
            'sobra_tubig': 'Sobra sa Tubig',
            'kulang_abono': 'Kulang Abono',
            'peste_sakit': 'Peste/Sakit',
            'damo': 'Damo',
            'soil_problem': 'Soil Problem',
            'lodging': 'Lodging',
            'nutrient_deficiency': 'Nutrient Deficiency',
            'pangit_binhi': 'Pangit na Binhi',
            'unknown': 'Di Ko Alam'
        };

        if ($this.hasClass('selected')) {
            toastr.info(`Added: ${reasonLabels[reason]}`, 'Reason Added');
        } else {
            toastr.info(`Removed: ${reasonLabels[reason]}`, 'Reason Removed');
        }
    });

    // Helper function to update reason selections
    function updateReasonSelections() {
        const selected = [];
        $('.reason-box.selected').each(function() {
            selected.push($(this).data('reason'));
        });
        $('#low_yield_reasons').val(selected.join(','));
    }

    // Step 9: Personal yield input handler
    $('#average_yield_input').on('input', function() {
        $('#average_yield').val($(this).val());
    });

    $('#yield_unit_select').on('change', function() {
        $('#yield_unit').val($(this).val());
    });

    // Step 9: Yield preset buttons
    $('.yield-preset-btn').on('click', function() {
        const yield_val = $(this).data('yield');
        const unit = $(this).data('unit');
        $('#average_yield_input').val(yield_val);
        $('#yield_unit_select').val(unit);
        $('#average_yield').val(yield_val);
        $('#yield_unit').val(unit);

        // Highlight selected preset
        $('.yield-preset-btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');

        toastr.success(`${yield_val} ${unit} selected!`, 'Yield Set');
    });

    // Step 10: Neighbor yield input handler
    $('#neighbor_yield_input').on('input', function() {
        $('#neighbor_yield').val($(this).val());
    });

    $('#neighbor_yield_unit_select').on('change', function() {
        $('#neighbor_yield_unit').val($(this).val());
    });

    // Step 10: Neighbor yield preset buttons
    $('.neighbor-yield-preset-btn').on('click', function() {
        const yield_val = $(this).data('yield');
        const unit = $(this).data('unit');
        $('#neighbor_yield_input').val(yield_val);
        $('#neighbor_yield_unit_select').val(unit);
        $('#neighbor_yield').val(yield_val);
        $('#neighbor_yield_unit').val(unit);

        // Highlight selected preset
        $('.neighbor-yield-preset-btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');

        toastr.success(`${yield_val} ${unit} selected!`, 'Neighbor Yield Set');
    });

    // Step 12: Soil Test answer selection
    $('.soil-test-answer-box').on('click', function() {
        const $this = $(this);
        const answer = $this.data('answer');

        // Remove selected from all answer boxes
        $('.soil-test-answer-box').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#has_soil_test').val(answer);

        // Show/hide encoding section based on answer
        if (answer === 'yes') {
            $('#soil-test-encoding-section').removeClass('d-none').hide().slideDown(300);
            $('#soil-test-hint').html('<i class="bx bx-edit me-1"></i>I-encode ang iyong soil test values sa ibaba');
        } else {
            $('#soil-test-encoding-section').slideUp(300, function() {
                $(this).addClass('d-none');
            });
            // Clear all soil test inputs when "Wala" is selected
            $('#soil_ph, #soil_ec, #soil_om, #soil_n, #soil_p, #soil_k').val('');
            $('#soil_ca, #soil_mg, #soil_na').val('');
            $('#soil_zn, #soil_b, #soil_fe, #soil_mn, #soil_cu').val('');
            $('#soil_cec').val('');
            $('#soil_texture_lab').val('');
            $('#soil-test-hint').html('<i class="bx bx-check-circle text-success me-1"></i>Okay, walang soil test. Magba-base tayo sa visual indicators.');
        }

        // Show toast
        const answerText = answer === 'yes' ? 'Oo (Meron)' : 'Wala';
        toastr.success(`Selected: ${answerText}`, 'Answer Recorded');
    });

    // Step 8: Soil Type selection (multi-select)
    $('.soil-selection-box').on('click', function() {
        const $this = $(this);
        const soil = $this.data('soil');

        const soilNames = {
            'sandy': 'Mabuhangin (Sandy Soil)',
            'loamy': 'Buhaghag na Lupa (Loamy Soil)',
            'clay_loose': 'Clay na Buhaghag (Loose Clay)',
            'clay_sticky': 'Clay na Malapot (Sticky Clay)',
            'rocky': 'Mabato (Rocky Soil)',
            'silty': 'Pinong Malapulbos (Silty Soil)',
            'waterlogged': 'Laging Basa (Waterlogged)',
            'sodic': 'Mabilis mag Bitak Bitak (Sodic Soil)',
            'unknown': 'Di Ko Alam (Unknown)'
        };

        // Toggle selection on clicked box
        $this.toggleClass('selected');

        // Get all selected soil types
        const selectedSoils = [];
        $('.soil-selection-box.selected').each(function() {
            selectedSoils.push($(this).data('soil'));
        });

        // Update hidden input with comma-separated values
        $('#soil_type').val(selectedSoils.join(','));

        // Update hint text
        if (selectedSoils.length === 0) {
            $('#soil-selection-hint').html('<i class="bx bx-info-circle me-1"></i>Select the type(s) of soil in your farm');
        } else if (selectedSoils.length === 1) {
            $('#soil-selection-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected: <strong>${soilNames[selectedSoils[0]]}</strong>`);
        } else {
            const names = selectedSoils.map(s => soilNames[s]).join(', ');
            $('#soil-selection-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected (${selectedSoils.length}): <strong>${names}</strong>`);
        }

        // Show toast
        if ($this.hasClass('selected')) {
            toastr.success(`Added: ${soilNames[soil]}`, 'Soil Selected');
        } else {
            toastr.info(`Removed: ${soilNames[soil]}`, 'Soil Removed');
        }
    });

    // Step 8: Soil Texture selection (single select)
    $('.texture-selection-box').on('click', function(e) {
        // Don't trigger selection when clicking info button
        if ($(e.target).closest('.texture-info-btn').length) {
            return;
        }

        const $this = $(this);
        const texture = $this.data('texture');

        // Remove selected from all texture boxes (single select)
        $('.texture-selection-box').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#soil_texture').val(texture);

        // Update hint
        const textureNames = {
            'sandy': 'Mabuhangin (Sandy)',
            'loam': 'Loam (Ideal)',
            'clay': 'Malagkit / Clay',
            'unknown': 'Halo-halo / Unknown'
        };
        $('#texture-selection-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected: <strong>${textureNames[texture]}</strong>`);

        // Show toast
        toastr.success(`Soil Texture: ${textureNames[texture]}`, 'Selected');
    });

    // Step 9: pH Clue yes/no selection
    $('.ph-answer-btn').on('click', function() {
        const $this = $(this);
        const answer = $this.data('answer');
        const field = $this.data('field');
        const $questionBox = $this.closest('.ph-question-box');

        // Remove selected from siblings
        $this.siblings('.ph-answer-btn').removeClass('selected-yes selected-no');

        // Add appropriate selected class
        if (answer === 'yes') {
            $this.addClass('selected-yes');
        } else {
            $this.addClass('selected-no');
        }

        // Update hidden input
        $('#' + field).val(answer);

        // Mark question box as answered
        $questionBox.addClass('has-answer');

        // Update hint
        updatePhClueHint();

        // Show toast
        toastr.info(`Answer recorded: ${answer === 'yes' ? 'Oo' : 'Hindi'}`, 'Noted');
    });

    // Step 10: Soil Indicators (Multi-Select)
    $('.soil-indicator-box').on('click', function(e) {
        // Don't trigger selection when clicking info button
        if ($(e.target).closest('.indicator-info-btn').length) {
            return;
        }

        const $this = $(this);
        const indicator = $this.data('indicator');

        // Special handling for "none" option
        if (indicator === 'none') {
            // If none is selected, deselect all others
            $('.soil-indicator-box').removeClass('selected');
            $this.addClass('selected');
            $('#soil_indicators').val('none');
            updateSoilIndicatorHint();
            toastr.success('Wala sa mga ito - healthy soil!', 'Selected');
            return;
        }

        // If selecting an indicator, deselect "none"
        $('.soil-indicator-box[data-indicator="none"]').removeClass('selected');

        // Toggle selection
        $this.toggleClass('selected');

        // Update hidden input with all selected indicators
        const selected = [];
        $('.soil-indicator-box.selected').each(function() {
            selected.push($(this).data('indicator'));
        });
        $('#soil_indicators').val(selected.join(','));

        // Update hint
        updateSoilIndicatorHint();

        // Show toast
        if ($this.hasClass('selected')) {
            toastr.info('Indicator selected', 'Added');
        } else {
            toastr.info('Indicator removed', 'Removed');
        }
    });

    // Helper function to update soil indicator hint
    function updateSoilIndicatorHint() {
        const selectedCount = $('.soil-indicator-box.selected').length;
        const isNoneSelected = $('.soil-indicator-box[data-indicator="none"]').hasClass('selected');

        if (isNoneSelected) {
            $('#soil-indicator-hint').html('<i class="bx bx-check-circle text-success me-1"></i>Healthy soil - walang problema!');
        } else if (selectedCount > 0) {
            $('#soil-indicator-hint').html(`<i class="bx bx-check-circle text-primary me-1"></i>${selectedCount} indicator${selectedCount > 1 ? 's' : ''} selected`);
        } else {
            $('#soil-indicator-hint').html('<i class="bx bx-info-circle me-1"></i>Pumili ng mga nakikita mo sa iyong bukid (pwedeng marami ang piliin)');
        }
    }

    // Step 11: Drainage selection (single select)
    $('.drainage-selection-box').on('click', function(e) {
        // Don't trigger selection when clicking info button
        if ($(e.target).closest('.drainage-info-btn').length) {
            return;
        }

        const $this = $(this);
        const drainage = $this.data('drainage');

        // Remove selected from all drainage boxes (single select)
        $('.drainage-selection-box').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#soil_drainage').val(drainage);

        // Update hint
        const drainageNames = {
            'fast': 'Mabilis Mawala Tubig (Fast)',
            'moderate': 'Sakto Lang (Moderate)',
            'slow': 'Mabagal / Laging Basa (Slow)'
        };
        $('#drainage-selection-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected: <strong>${drainageNames[drainage]}</strong>`);

        // Show toast
        toastr.success(`Drainage: ${drainageNames[drainage]}`, 'Selected');
    });

    // Step 11: Soil Suspicion selection (multi-select, or none)
    $('.suspicion-box').on('click', function(e) {
        // Don't trigger selection when clicking info button
        if ($(e.target).closest('.suspicion-info-btn').length) {
            return;
        }

        const $this = $(this);
        const suspicion = $this.data('suspicion');

        // If clicking "none", clear all other selections
        if (suspicion === 'none') {
            $('.suspicion-box').removeClass('selected');
            $this.addClass('selected');
        } else {
            // If selecting a suspicion, remove "none" selection
            $('.suspicion-box[data-suspicion="none"]').removeClass('selected');
            $this.toggleClass('selected');
        }

        // Update hidden input with all selected suspicions
        updateSuspicionSelections();

        // Update hint
        const selectedCount = $('.suspicion-box.selected').length;
        if (selectedCount === 0) {
            $('#suspicion-selection-hint').html('<i class="bx bx-info-circle me-1"></i>Select any suspected soil problems');
        } else if (suspicion === 'none' && $this.hasClass('selected')) {
            $('#suspicion-selection-hint').html('<i class="bx bx-check-circle text-success me-1"></i>No suspected problems - great!');
            toastr.success('No suspected soil problems!', 'Great!');
        } else {
            $('#suspicion-selection-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>${selectedCount} suspicion(s) selected`);
            if ($this.hasClass('selected')) {
                toastr.info(`Added: ${getSuspicionLabel(suspicion)}`, 'Added');
            } else {
                toastr.info(`Removed: ${getSuspicionLabel(suspicion)}`, 'Removed');
            }
        }
    });

    // Helper function to update suspicion selections
    function updateSuspicionSelections() {
        const selected = [];
        $('.suspicion-box.selected').each(function() {
            selected.push($(this).data('suspicion'));
        });
        $('#soil_problems').val(selected.join(','));
    }

    // Helper function to get suspicion label
    function getSuspicionLabel(suspicion) {
        const labels = {
            'sodic_alkaline': 'Sodic/Alkaline',
            'acidic': 'Acidic',
            'compaction': 'Compaction/Hardpan',
            'low_organic': 'Low Organic Matter',
            'none': 'Walang Hinala'
        };
        return labels[suspicion] || suspicion;
    }

    // Step 12: Irrigation Type selection (Multi-Select - one or more)
    $('.irrigation-box:not(.reliability-box)').on('click', function() {
        const $this = $(this);
        const irrigation = $this.data('irrigation');

        // Toggle selection (multi-select)
        $this.toggleClass('selected');

        // Update hidden input with all selected irrigation types
        const selected = [];
        $('.irrigation-box:not(.reliability-box).selected').each(function() {
            selected.push($(this).data('irrigation'));
        });
        $('#irrigation_type').val(selected.join(','));

        // Update hint
        const irrigationLabels = {
            'nia_canal': 'Irrigated (NIA/Canal)',
            'deepwell': 'Deepwell/Pump',
            'rainfed': 'Rainfed (Asa sa Ulan)',
            'mixed': 'Mixed'
        };

        if (selected.length > 0) {
            const selectedNames = selected.map(s => irrigationLabels[s]).join(', ');
            $('#irrigation-type-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected: <strong>${selectedNames}</strong>`);
        } else {
            $('#irrigation-type-hint').html('<i class="bx bx-info-circle me-1"></i>Select your irrigation method(s) - pwedeng marami');
        }

        // Show toast
        if ($this.hasClass('selected')) {
            toastr.info(`Added: ${irrigationLabels[irrigation]}`, 'Selected');
        } else {
            toastr.info(`Removed: ${irrigationLabels[irrigation]}`, 'Removed');
        }
    });

    // Step 13: Water reliability selection
    $('.reliability-box').on('click', function() {
        const $this = $(this);
        const reliability = $this.data('reliability');

        // Remove selected from all reliability boxes (single select)
        $('.reliability-box').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#water_reliability').val(reliability);

        // Update hint
        const reliabilityLabels = {
            'always': 'Palaging May Tubig',
            'sometimes': 'Minsan Nawawala',
            'often_lacking': 'Madalas Kulang'
        };
        $('#water-reliability-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected: <strong>${reliabilityLabels[reliability]}</strong>`);

        // Show toast
        toastr.info(`Water Reliability: ${reliabilityLabels[reliability]}`, 'Selected');
    });

    // Step 14: Goal selection
    $('.goal-selection-box').on('click', function() {
        const $this = $(this);
        const goal = $this.data('goal');

        // Remove selected from all goal boxes
        $('.goal-selection-box').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#main_goal').val(goal);

        // Update hint text
        const goalNames = {
            'high_yield': 'Pinaka Mataas na Ani (Maximum Harvest)',
            'cost_effective': 'Aani Pero Tipid sa Gastos (Harvest with Savings)',
            'balanced': 'Sakto Lang (Kaya Gumastos, Hindi Sobra)'
        };
        $('#goal-selection-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected: <strong>${goalNames[goal]}</strong>`);

        // Show success toast
        toastr.success(`Goal: ${goalNames[goal]}`, 'Goal Selected');
    });

    // Step 15: Inclusion selection (multi-select)
    $('.inclusion-box:not(.locked)').on('click', function() {
        const $this = $(this);
        const inclusion = $this.data('inclusion');

        // Toggle selected state
        $this.toggleClass('selected');

        // Update hidden input with all selected inclusions
        updateInclusionSelections();

        // Show toast
        if ($this.hasClass('selected')) {
            toastr.success(`Added: ${getInclusionLabel(inclusion)}`, 'Added');
        } else {
            toastr.info(`Removed: ${getInclusionLabel(inclusion)}`, 'Removed');
        }
    });

    // Helper function to get inclusion label
    function getInclusionLabel(inclusion) {
        const labels = {
            'granular_fertilizer': 'Granular Fertilizer',
            'herbicide': 'Herbicide',
            'pesticide': 'Pesticide Protection',
            'fungicide': 'Fungicide Protection',
            'bacteria': 'Bacteria Protection',
            'foliar': 'Foliar Application',
            'biostimulants': 'Biostimulants',
            'soil_conditioner': 'Soil Conditioner',
            'root_ecosystem': 'Root Ecosystem'
        };
        return labels[inclusion] || inclusion;
    }

    // Update hidden input with selected inclusions
    function updateInclusionSelections() {
        const selected = [];
        // Always include granular_fertilizer
        selected.push('granular_fertilizer');

        // Add other selected items
        $('.inclusion-box.selected:not(.locked)').each(function() {
            selected.push($(this).data('inclusion'));
        });

        $('#recommendation_inclusions').val(selected.join(','));

        // Update hint with count
        const count = selected.length;
        const protectionText = count > 1 ? `${count} items selected` : '1 item selected';
        $('#inclusion-selection-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>${protectionText} • Granular Fertilizer is always included`);
    }

    // Step 17: Leaf Symptoms (Multi-Select)
    $('.leaf-symptom-box').on('click', function(e) {
        // Don't trigger selection when clicking info button
        if ($(e.target).closest('.symptom-info-btn').length) {
            return;
        }

        const $this = $(this);
        const symptom = $this.data('symptom');

        // Special handling for "none" option
        if (symptom === 'none') {
            // If none is selected, deselect all others
            $('.leaf-symptom-box').removeClass('selected');
            $this.addClass('selected');
            $('#leaf_symptoms').val('none');
            updateLeafSymptomHint();
            toastr.success('Wala sa mga ito - malusog ang halaman!', 'Selected');
            return;
        }

        // If selecting a symptom, deselect "none"
        $('.leaf-symptom-box[data-symptom="none"]').removeClass('selected');

        // Toggle selection
        $this.toggleClass('selected');

        // Update hidden input with all selected symptoms
        const selected = [];
        $('.leaf-symptom-box.selected').each(function() {
            selected.push($(this).data('symptom'));
        });
        $('#leaf_symptoms').val(selected.join(','));

        // Update hint
        updateLeafSymptomHint();

        // Show toast
        if ($this.hasClass('selected')) {
            toastr.info('Symptom selected', 'Added');
        } else {
            toastr.info('Symptom removed', 'Removed');
        }
    });

    // Helper function to update leaf symptom hint
    function updateLeafSymptomHint() {
        const selectedCount = $('.leaf-symptom-box.selected').length;
        const isNoneSelected = $('.leaf-symptom-box[data-symptom="none"]').hasClass('selected');

        if (isNoneSelected) {
            $('#leaf-symptom-hint').html('<i class="bx bx-check-circle text-success me-1"></i>Malusog ang halaman - walang symptoms!');
        } else if (selectedCount > 0) {
            $('#leaf-symptom-hint').html(`<i class="bx bx-check-circle text-primary me-1"></i>${selectedCount} symptom${selectedCount > 1 ? 's' : ''} selected`);
        } else {
            $('#leaf-symptom-hint').html('<i class="bx bx-info-circle me-1"></i>Pumili ng mga symptoms na napansin mo sa nakaraang cropping (pwedeng marami)');
        }
    }

    // Step 18: Pest Selection (Multi-Select)
    $('.pest-box').on('click', function(e) {
        // Don't trigger selection when clicking info button
        if ($(e.target).closest('.pest-info-btn').length) {
            return;
        }

        const $this = $(this);
        const pest = $this.data('pest');

        // Special handling for "none" option
        if (pest === 'none') {
            // If none is selected, deselect all others
            $('.pest-box').removeClass('selected');
            $this.addClass('selected');
            $('#usual_pests').val('none');
            toastr.success('Walang pest problems sa area!', 'Selected');
            return;
        }

        // If selecting a pest, deselect "none"
        $('.pest-box[data-pest="none"]').removeClass('selected');

        // Toggle selection
        $this.toggleClass('selected');

        // Update hidden input with all selected pests
        const selected = [];
        $('.pest-box.selected').each(function() {
            const pestVal = $(this).data('pest');
            if (pestVal !== 'none') {
                selected.push(pestVal);
            }
        });
        $('#usual_pests').val(selected.join(','));

        // Show toast
        if ($this.hasClass('selected')) {
            toastr.info('Pest added to list', 'Added');
        } else {
            toastr.info('Pest removed from list', 'Removed');
        }
    });

    // Spray approach selection handler
    $('.spray-approach-box').on('click', function() {
        const $this = $(this);
        const approach = $this.data('approach');

        // Remove selected from all boxes
        $('.spray-approach-box').removeClass('selected');

        // Add selected to clicked box
        $this.addClass('selected');

        // Update hidden input
        $('#spray_approach').val(approach);

        // Update hint text
        const approachName = approach === 'preventive' ? 'Preventive Spray' : 'Kapag May Symptoms (Threshold-based)';
        $('#spray-selection-hint').html(`<i class="bx bx-check-circle text-success me-1"></i>Selected: <strong>${approachName}</strong>`);

        // Show success toast
        toastr.success(`${approachName} selected!`, 'Spray Approach');
    });

    // Previous button click
    $('#prev-btn').click(function() {
        if (isAnimating) return;
        let targetStep = currentStep - 1;
        showStep(targetStep, 'back');
    });

    // Next button click
    $('#next-btn').click(function() {
        if (isAnimating) return;
        if (validateStep(currentStep)) {
            let targetStep = currentStep + 1;
            showStep(targetStep, 'forward');
        }
    });

    // Form submit
    $('#recommendation-wizard-form').on('submit', function(e) {
        e.preventDefault();

        if (!validateStep(currentStep)) {
            return;
        }

        const $submitBtn = $('#submit-btn');
        $submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Creating...');

        // TODO: Add form submission logic here
        // For now, just show a message
        setTimeout(function() {
            toastr.info('Form submission will be implemented when steps are configured.', 'Info');
            $submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Create Recommendation');
        }, 1000);
    });

    // Step label click (allow clicking on step labels to navigate)
    $('.step-label').click(function() {
        if (isAnimating) return;

        const targetStep = parseInt($(this).data('step'));

        // Same step - do nothing
        if (targetStep === currentStep) return;

        // Determine direction
        const direction = targetStep > currentStep ? 'forward' : 'back';

        // Only allow going to previous steps or current step
        if (targetStep < currentStep) {
            showStep(targetStep, direction);
        } else {
            // Validate all steps before the target step
            let canProceed = true;
            for (let i = currentStep; i < targetStep; i++) {
                if (!validateStep(i)) {
                    canProceed = false;
                    toastr.warning('Please complete the current step before proceeding.', 'Warning');
                    break;
                }
            }
            if (canProceed) {
                showStep(targetStep, direction);
            }
        }
    });

    // Remove validation error when user types in manual variety name
    $('#manual_variety_name').on('input', function() {
        $(this).removeClass('is-invalid');
    });

    // Prevent text cursor on non-interactive elements
    $(document).on('click', '.card', function(e) {
        const target = e.target;
        const tagName = target.tagName.toLowerCase();
        const isInteractive = ['input', 'textarea', 'select', 'button', 'a'].includes(tagName) ||
                              $(target).hasClass('btn') ||
                              $(target).closest('button, .btn, a').length > 0;

        if (!isInteractive) {
            document.activeElement.blur();
        }
    });

    // =============================================
    // VARIETY DETAIL MODAL FUNCTIONALITY
    // =============================================

    let currentViewingVariety = null;

    // View button click handler (delegated)
    $(document).on('click', '.variety-view-btn', function(e) {
        e.stopPropagation(); // Prevent selecting the variety when clicking view

        const varietyId = $(this).data('id');
        showVarietyDetail(varietyId);
    });

    // Function to show variety detail modal
    function showVarietyDetail(varietyId) {
        // Show modal with loading state
        $('#variety-detail-loading').removeClass('d-none');
        $('#variety-detail-content').addClass('d-none');
        $('#varietyDetailModal').modal('show');

        // Fetch variety details from API
        $.ajax({
            url: '{{ route("knowledgebase.crop-breeds.api.detail") }}',
            type: 'GET',
            data: { id: varietyId },
            success: function(response) {
                if (response.success && response.breed) {
                    currentViewingVariety = response.breed;
                    populateVarietyModal(response.breed);
                    $('#variety-detail-loading').addClass('d-none');
                    $('#variety-detail-content').removeClass('d-none');
                } else {
                    toastr.error('Could not load variety details.', 'Error');
                    $('#varietyDetailModal').modal('hide');
                }
            },
            error: function() {
                toastr.error('Failed to load variety details.', 'Error');
                $('#varietyDetailModal').modal('hide');
            }
        });
    }

    // Function to populate modal with variety data
    function populateVarietyModal(breed) {
        // Image
        if (breed.imagePath) {
            $('#variety-detail-image-container').html(`
                <img src="{{ asset('') }}${breed.imagePath}" alt="${escapeHtml(breed.name)}" class="variety-detail-image">
            `);
        } else {
            $('#variety-detail-image-container').html(`
                <div class="variety-detail-image-placeholder">
                    <i class="bx bx-image"></i>
                </div>
            `);
        }

        // Basic info
        $('#variety-detail-name').text(breed.name || '-');
        $('#variety-detail-manufacturer').text(breed.manufacturer || 'Unknown manufacturer');

        // Badges
        const cropLabel = breed.cropType === 'corn' ? 'Corn' : 'Rice';
        $('#variety-detail-crop-badge').text(cropLabel);

        let breedLabel = '-';
        if (breed.breedType) {
            breedLabel = breed.breedType.charAt(0).toUpperCase() + breed.breedType.slice(1);
        } else if (breed.cornType) {
            breedLabel = breed.cornType.charAt(0).toUpperCase() + breed.cornType.slice(1);
        }
        $('#variety-detail-breed-badge').text(breedLabel);

        // Details
        $('#variety-detail-yield').text(breed.potentialYield || 'Not specified');
        $('#variety-detail-maturity').text(breed.maturityDays || 'Not specified');

        // Gene protection
        if (breed.geneProtection && breed.geneProtection.length > 0) {
            let genesHtml = '';
            breed.geneProtection.forEach(function(gene) {
                genesHtml += `<span class="badge bg-success">${escapeHtml(gene)}</span>`;
            });
            $('#variety-detail-genes').html(genesHtml);
            $('#variety-detail-genes-container').removeClass('d-none');
        } else {
            $('#variety-detail-genes-container').addClass('d-none');
        }

        // Characteristics
        if (breed.characteristics) {
            $('#variety-detail-characteristics').text(breed.characteristics);
            $('#variety-detail-characteristics-container').removeClass('d-none');
        } else {
            $('#variety-detail-characteristics-container').addClass('d-none');
        }

        // Related information
        if (breed.relatedInformation) {
            $('#variety-detail-info').text(breed.relatedInformation);
            $('#variety-detail-info-container').removeClass('d-none');
        } else {
            $('#variety-detail-info-container').addClass('d-none');
        }

        // Source URL
        if (breed.sourceUrl) {
            $('#variety-detail-source').html(`<a href="${escapeHtml(breed.sourceUrl)}" target="_blank">${escapeHtml(breed.sourceUrl)}</a>`);
            $('#variety-detail-source-container').removeClass('d-none');
        } else {
            $('#variety-detail-source-container').addClass('d-none');
        }

        // Brochure Section (in Details tab)
        if (breed.brochurePath) {
            const brochureUrl = '{{ asset('') }}' + breed.brochurePath;
            $('#brochure-pdf-frame').attr('src', brochureUrl);
            $('#brochure-download-btn').attr('href', brochureUrl);
            $('#brochure-new-tab-btn').attr('href', brochureUrl);
            $('#variety-detail-brochure-section').removeClass('d-none');
        } else {
            $('#variety-detail-brochure-section').addClass('d-none');
            $('#brochure-pdf-frame').attr('src', '');
        }

        // Reset compare tab state when viewing a new variety
        resetCompareTab();
    }

    // Compare pagination variables
    let compareCurrentPage = 1;
    let comparePerPage = 10;
    let compareAllVarieties = [];
    let compareFilteredVarieties = [];

    // Reset compare tab to search state
    function resetCompareTab() {
        $('#compare-search-section').removeClass('d-none');
        $('#compare-selected-section').addClass('d-none');
        $('#compare-search-input').val('');
        $('#compare-clear-search').addClass('d-none');
        compareCurrentPage = 1;
        // Load varieties by default
        loadCompareVarieties();
    }

    // Load varieties for compare tab
    function loadCompareVarieties() {
        const cropType = $('#crop_type').val();
        const breedType = $('#breed_type').val();
        const cornType = $('#corn_type').val();

        const params = new URLSearchParams();
        if (cropType === 'palay') {
            params.append('crop_type', 'rice');
            if (breedType) params.append('breed_type', breedType);
        } else if (cropType === 'corn') {
            params.append('crop_type', 'corn');
            if (cornType) params.append('corn_type', cornType);
        }

        // Show loading
        $('#compare-search-results').html(`
            <div class="text-center py-4">
                <i class="bx bx-loader-alt bx-spin" style="font-size: 1.5rem; color: #556ee6;"></i>
                <p class="text-secondary mt-2 mb-0">Loading varieties...</p>
            </div>
        `);

        $.ajax({
            url: '{{ route("knowledgebase.crop-breeds.api.breeds") }}?' + params.toString(),
            type: 'GET',
            success: function(response) {
                if (response.success && response.breeds) {
                    // Exclude currently viewing variety
                    compareAllVarieties = response.breeds.filter(function(breed) {
                        return !(currentViewingVariety && breed.id === currentViewingVariety.id);
                    });
                    compareFilteredVarieties = compareAllVarieties;
                    compareCurrentPage = 1;
                    renderCompareResults();
                } else {
                    $('#compare-search-results').html(`
                        <div class="compare-search-placeholder">
                            <i class="bx bx-leaf"></i>
                            <p class="text-dark mb-1">No varieties available</p>
                            <small class="text-secondary">No other varieties found for this type</small>
                        </div>
                    `);
                }
            },
            error: function() {
                $('#compare-search-results').html(`
                    <div class="compare-search-placeholder">
                        <i class="bx bx-error-circle text-danger"></i>
                        <p class="text-dark mb-1">Failed to load varieties</p>
                        <small class="text-secondary">Please try again</small>
                    </div>
                `);
            }
        });
    }

    // Render compare results with pagination
    function renderCompareResults() {
        if (compareFilteredVarieties.length === 0) {
            $('#compare-search-results').html(`
                <div class="compare-search-placeholder">
                    <i class="bx bx-search-alt"></i>
                    <p class="text-dark mb-1">No varieties found</p>
                    <small class="text-secondary">Try a different search term</small>
                </div>
            `);
            return;
        }

        const totalPages = Math.ceil(compareFilteredVarieties.length / comparePerPage);
        const startIndex = (compareCurrentPage - 1) * comparePerPage;
        const endIndex = Math.min(startIndex + comparePerPage, compareFilteredVarieties.length);
        const pageVarieties = compareFilteredVarieties.slice(startIndex, endIndex);

        let html = '<div class="compare-results-list">';
        pageVarieties.forEach(function(breed) {
            html += `
                <div class="compare-item" data-id="${breed.id}">
                    <div class="d-flex align-items-center">
                        <div class="compare-item-icon me-2">
                            ${breed.imagePath ?
                                `<img src="{{ asset('') }}${breed.imagePath}" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;">` :
                                '<i class="bx bx-leaf text-success" style="font-size: 1.5rem;"></i>'
                            }
                        </div>
                        <div class="flex-grow-1">
                            <div class="compare-item-name text-dark fw-medium">${escapeHtml(breed.name)}</div>
                            <div class="compare-item-meta text-secondary small">${escapeHtml(breed.manufacturer || 'Unknown')}</div>
                        </div>
                        <i class="bx bx-chevron-right text-secondary"></i>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        // Add pagination if needed
        if (totalPages > 1) {
            html += `
                <div class="compare-pagination mt-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-secondary">Showing ${startIndex + 1}-${endIndex} of ${compareFilteredVarieties.length}</small>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary compare-page-btn" data-page="prev" ${compareCurrentPage === 1 ? 'disabled' : ''}>
                                <i class="bx bx-chevron-left"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary disabled">${compareCurrentPage} / ${totalPages}</button>
                            <button type="button" class="btn btn-outline-secondary compare-page-btn" data-page="next" ${compareCurrentPage === totalPages ? 'disabled' : ''}>
                                <i class="bx bx-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        } else {
            html += `<div class="text-center mt-2"><small class="text-secondary">${compareFilteredVarieties.length} varieties found</small></div>`;
        }

        $('#compare-search-results').html(html);
    }

    // Compare pagination click handler
    $(document).on('click', '.compare-page-btn:not(:disabled)', function() {
        const action = $(this).data('page');
        if (action === 'prev' && compareCurrentPage > 1) {
            compareCurrentPage--;
        } else if (action === 'next') {
            const totalPages = Math.ceil(compareFilteredVarieties.length / comparePerPage);
            if (compareCurrentPage < totalPages) {
                compareCurrentPage++;
            }
        }
        renderCompareResults();
    });

    // Populate compare tab with variety details
    function populateCompareDetails(breed) {
        // Image
        if (breed.imagePath) {
            $('#compare-detail-image-container').html(`
                <img src="{{ asset('') }}${breed.imagePath}" alt="${escapeHtml(breed.name)}" class="variety-detail-image">
            `);
        } else {
            $('#compare-detail-image-container').html(`
                <div class="variety-detail-image-placeholder">
                    <i class="bx bx-image"></i>
                </div>
            `);
        }

        // Basic info
        $('#compare-detail-name').text(breed.name || '-');
        $('#compare-detail-manufacturer').text(breed.manufacturer || 'Unknown manufacturer');

        // Badges
        const cropLabel = breed.cropType === 'corn' ? 'Corn' : 'Rice';
        $('#compare-detail-crop-badge').text(cropLabel);

        let breedLabel = '-';
        if (breed.breedType) {
            breedLabel = breed.breedType.charAt(0).toUpperCase() + breed.breedType.slice(1);
        } else if (breed.cornType) {
            breedLabel = breed.cornType.charAt(0).toUpperCase() + breed.cornType.slice(1);
        }
        $('#compare-detail-breed-badge').text(breedLabel);

        // Details
        $('#compare-detail-yield').text(breed.potentialYield || 'Not specified');
        $('#compare-detail-maturity').text(breed.maturityDays || 'Not specified');

        // Gene protection
        if (breed.geneProtection && breed.geneProtection.length > 0) {
            let genesHtml = '';
            breed.geneProtection.forEach(function(gene) {
                genesHtml += `<span class="badge bg-success">${escapeHtml(gene)}</span>`;
            });
            $('#compare-detail-genes').html(genesHtml);
            $('#compare-detail-genes-container').removeClass('d-none');
        } else {
            $('#compare-detail-genes-container').addClass('d-none');
        }

        // Characteristics
        if (breed.characteristics) {
            $('#compare-detail-characteristics').text(breed.characteristics);
            $('#compare-detail-characteristics-container').removeClass('d-none');
        } else {
            $('#compare-detail-characteristics-container').addClass('d-none');
        }

        // Related information
        if (breed.relatedInformation) {
            $('#compare-detail-info').text(breed.relatedInformation);
            $('#compare-detail-info-container').removeClass('d-none');
        } else {
            $('#compare-detail-info-container').addClass('d-none');
        }

        // Brochure Section for Compare tab
        if (breed.brochurePath) {
            const brochureUrl = '{{ asset('') }}' + breed.brochurePath;
            $('#compare-brochure-pdf-frame').attr('src', brochureUrl);
            $('#compare-brochure-download-btn').attr('href', brochureUrl);
            $('#compare-brochure-new-tab-btn').attr('href', brochureUrl);
            $('#compare-detail-brochure-section').removeClass('d-none');
        } else {
            $('#compare-detail-brochure-section').addClass('d-none');
            $('#compare-brochure-pdf-frame').attr('src', '');
        }
    }

    // Select variety from modal
    $('#select-variety-from-modal').on('click', function() {
        if (currentViewingVariety) {
            selectVariety(
                currentViewingVariety.id,
                currentViewingVariety.name,
                currentViewingVariety.manufacturer,
                currentViewingVariety.potentialYield
            );
            $('#varietyDetailModal').modal('hide');
        }
    });

    // Reset modal state when hidden
    $('#varietyDetailModal').on('hidden.bs.modal', function() {
        currentViewingVariety = null;
        compareSelectedVariety = null;
        resetCompareTab();
    });

    // Variable to store compare selected variety
    let compareSelectedVariety = null;

    // Manual entry - Back to Search button
    $('#manual-back-to-search').on('click', function() {
        hideSection($('#manual-entry-section'), 'slide', function() {
            showSection($('#variety-search-container'), 'slide');
            // Focus on search
            setTimeout(function() {
                $('#variety_search').focus();
            }, 100);
        });
        $('#variety_id').val('');
        // Clear manual entry fields
        $('#manual_variety_name').val('').removeClass('is-invalid');
        $('#manual_manufacturer').val('');
        $('#manual_yield').val('');
        $('#manual_maturity').val('');
        $('#manual_characteristics').val('');
    });

    // Compare tab - Search input handler (filters locally loaded varieties)
    let compareSearchTimeout = null;
    $('#compare-search-input').on('input', function() {
        const searchTerm = $(this).val().trim();

        // Show/hide clear button
        if (searchTerm) {
            $('#compare-clear-search').removeClass('d-none');
        } else {
            $('#compare-clear-search').addClass('d-none');
        }

        // Debounce search
        clearTimeout(compareSearchTimeout);
        compareSearchTimeout = setTimeout(function() {
            filterCompareVarieties(searchTerm);
        }, 200);
    });

    // Filter compare varieties locally
    function filterCompareVarieties(searchTerm) {
        if (!searchTerm) {
            compareFilteredVarieties = compareAllVarieties;
        } else {
            const term = searchTerm.toLowerCase();
            compareFilteredVarieties = compareAllVarieties.filter(function(breed) {
                const name = (breed.name || '').toLowerCase();
                const manufacturer = (breed.manufacturer || '').toLowerCase();
                return name.includes(term) || manufacturer.includes(term);
            });
        }
        compareCurrentPage = 1;
        renderCompareResults();
    }

    // Compare tab - Clear search button
    $('#compare-clear-search').on('click', function() {
        $('#compare-search-input').val('');
        $(this).addClass('d-none');
        compareFilteredVarieties = compareAllVarieties;
        compareCurrentPage = 1;
        renderCompareResults();
        $('#compare-search-input').focus();
    });

    // Compare tab - Select variety from results
    $(document).on('click', '.compare-item', function() {
        const varietyId = $(this).data('id');

        // Show loading in selected section
        $('#compare-search-section').addClass('d-none');
        $('#compare-selected-section').removeClass('d-none').html(`
            <div class="text-center py-5">
                <i class="bx bx-loader-alt bx-spin" style="font-size: 2rem; color: #556ee6;"></i>
                <p class="text-secondary mt-2 mb-0">Loading variety details...</p>
            </div>
        `);

        // Fetch variety details
        $.ajax({
            url: '{{ route("knowledgebase.crop-breeds.api.detail") }}',
            type: 'GET',
            data: { id: varietyId },
            success: function(response) {
                if (response.success && response.breed) {
                    compareSelectedVariety = response.breed;

                    // Rebuild the selected section HTML
                    $('#compare-selected-section').html(`
                        <div class="compare-back-btn mb-3" id="compare-back-to-search">
                            <i class="bx bx-arrow-back"></i>
                            <span>Back to Search</span>
                        </div>

                        <div class="variety-detail-header">
                            <div id="compare-detail-image-container"></div>
                            <div class="variety-detail-title flex-grow-1">
                                <h4 id="compare-detail-name">-</h4>
                                <p class="text-secondary mb-2" id="compare-detail-manufacturer">-</p>
                                <div>
                                    <span class="badge bg-primary me-1" id="compare-detail-crop-badge">-</span>
                                    <span class="badge bg-info text-white" id="compare-detail-breed-badge">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="variety-detail-grid">
                            <div class="variety-detail-item">
                                <div class="variety-detail-label"><i class="bx bx-trending-up me-1"></i>Potential Yield</div>
                                <div class="variety-detail-value" id="compare-detail-yield">-</div>
                            </div>
                            <div class="variety-detail-item">
                                <div class="variety-detail-label"><i class="bx bx-calendar me-1"></i>Days to Maturity</div>
                                <div class="variety-detail-value" id="compare-detail-maturity">-</div>
                            </div>
                            <div class="variety-detail-item full-width" id="compare-detail-genes-container">
                                <div class="variety-detail-label"><i class="bx bx-shield me-1"></i>Gene Protection</div>
                                <div class="variety-detail-value variety-gene-badges" id="compare-detail-genes">-</div>
                            </div>
                            <div class="variety-detail-item full-width" id="compare-detail-characteristics-container">
                                <div class="variety-detail-label"><i class="bx bx-list-ul me-1"></i>Characteristics</div>
                                <div class="variety-detail-value variety-detail-characteristics" id="compare-detail-characteristics">-</div>
                            </div>
                            <div class="variety-detail-item full-width" id="compare-detail-info-container">
                                <div class="variety-detail-label"><i class="bx bx-info-circle me-1"></i>Related Information</div>
                                <div class="variety-detail-value variety-detail-characteristics" id="compare-detail-info">-</div>
                            </div>
                        </div>

                        <!-- Brochure Section for Compare -->
                        <div id="compare-detail-brochure-section" class="d-none mt-4">
                            <div class="brochure-section-header">
                                <h6 class="mb-0"><i class="bx bx-file-blank text-primary me-2"></i>Product Brochure</h6>
                                <div class="brochure-actions">
                                    <a href="#" id="compare-brochure-download-btn" class="btn btn-primary btn-sm" target="_blank">
                                        <i class="bx bx-download me-1"></i>Download
                                    </a>
                                    <a href="#" id="compare-brochure-new-tab-btn" class="btn btn-outline-secondary btn-sm" target="_blank">
                                        <i class="bx bx-link-external me-1"></i>Open
                                    </a>
                                </div>
                            </div>
                            <div class="brochure-preview-container">
                                <iframe id="compare-brochure-pdf-frame" class="brochure-pdf-frame"></iframe>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-primary" id="select-compare-variety">
                                <i class="bx bx-check me-1"></i>Select This Variety Instead
                            </button>
                        </div>
                    `);

                    populateCompareDetails(response.breed);
                } else {
                    toastr.error('Could not load variety details.', 'Error');
                    $('#compare-search-section').removeClass('d-none');
                    $('#compare-selected-section').addClass('d-none');
                }
            },
            error: function() {
                toastr.error('Failed to load variety details.', 'Error');
                $('#compare-search-section').removeClass('d-none');
                $('#compare-selected-section').addClass('d-none');
            }
        });
    });

    // Compare tab - Back to search
    $(document).on('click', '#compare-back-to-search', function() {
        compareSelectedVariety = null;
        $('#compare-selected-section').addClass('d-none');
        $('#compare-search-section').removeClass('d-none');
        $('#compare-search-input').focus();
    });

    // Compare tab - Select this variety instead
    $(document).on('click', '#select-compare-variety', function() {
        if (compareSelectedVariety) {
            // First update the currentViewingVariety
            currentViewingVariety = compareSelectedVariety;
            // Repopulate the details tab
            populateVarietyModal(compareSelectedVariety);
            // Switch to details tab
            $('#details-tab').tab('show');
            // Reset compare tab
            resetCompareTab();
            compareSelectedVariety = null;
            toastr.success('Switched to "' + currentViewingVariety.name + '"', 'Variety Changed');
        }
    });

    // =====================================================
    // VARIETY FINDER WIZARD (AI-POWERED)
    // =====================================================

    let finderCurrentStep = 1;
    const finderTotalSteps = 3; // 3 steps: Free-text, Budget, Protection
    let finderAnswers = {
        freeText: '',
        budget: null,
        farmSize: null,
        farmUnit: 'hectares',
        protection: []
    };

    // Open variety finder modal
    $('#open-variety-finder').on('click', function() {
        resetFinderWizard();
        $('#varietyFinderModal').modal('show');
    });

    // Reset wizard to initial state
    function resetFinderWizard() {
        finderCurrentStep = 1;
        finderAnswers = {
            freeText: '',
            budget: null,
            protection: []
        };

        // Reset UI
        $('.finder-step').removeClass('active');
        $('.finder-step[data-step="1"]').addClass('active');
        $('.finder-option').removeClass('selected');
        $('.finder-checkbox').removeClass('checked');
        $('.finder-checkbox input').prop('checked', false);
        $('#finder-freetext').val('');
        updateFinderProgress();
        updateFinderNavigation();

        // Reset results
        $('.finder-results-loading').removeClass('d-none');
        $('.finder-results-list').addClass('d-none').empty();
        $('.finder-no-results').addClass('d-none');
        $('.finder-ai-summary').addClass('d-none');
        $('.finder-results-subtitle').text('Sinusuri ng aming Smart Technician ang iyong mga pangangailangan...');
    }

    // Update progress dots
    function updateFinderProgress() {
        $('.finder-wizard-progress .progress-dot').each(function() {
            const step = $(this).data('step');
            $(this).removeClass('active completed');
            if (step === finderCurrentStep) {
                $(this).addClass('active');
            } else if (step < finderCurrentStep) {
                $(this).addClass('completed');
            }
        });
    }

    // Update navigation buttons
    function updateFinderNavigation() {
        $('#finder-prev-btn').prop('disabled', finderCurrentStep === 1);

        if (finderCurrentStep === finderTotalSteps) {
            $('#finder-next-btn').html('Kumuha ng Rekomendasyon<i class="bx bx-right-arrow-alt ms-1"></i>').removeClass('btn-primary btn-secondary').addClass('btn-success');
        } else if (finderCurrentStep > finderTotalSteps) {
            $('#finder-next-btn').html('<i class="bx bx-x me-1"></i>Isara').removeClass('btn-primary btn-success').addClass('btn-secondary');
        } else {
            $('#finder-next-btn').html('Susunod<i class="bx bx-chevron-right ms-1"></i>').removeClass('btn-secondary btn-success').addClass('btn-primary');
        }
    }

    // Skip button handler
    $(document).on('click', '.finder-skip-btn', function() {
        const skipTo = $(this).data('skip-to');
        if (skipTo === 'results') {
            // Capture current step data first
            if (finderCurrentStep === 1) {
                finderAnswers.freeText = $('#finder-freetext').val().trim();
            }

            // Validate: at least one step must have input
            const hasFreeText = finderAnswers.freeText && finderAnswers.freeText.trim().length > 0;
            const hasBudget = finderAnswers.budget && finderAnswers.budget.length > 0;
            const hasProtection = finderAnswers.protection && finderAnswers.protection.length > 0 && !(finderAnswers.protection.length === 1 && finderAnswers.protection[0] === 'none');

            if (!hasFreeText && !hasBudget && !hasProtection) {
                $('#finderValidationModal').modal('show');
                return;
            }

            // Skip to results
            finderCurrentStep = finderTotalSteps + 1;
            $('.finder-step').removeClass('active');
            $('.finder-step[data-step="results"]').addClass('active');
            updateFinderNavigation();
            findMatchingVarietiesWithAI();
        } else {
            // Skip to specific step
            finderCurrentStep = parseInt(skipTo);
            $('.finder-step').removeClass('active');
            $(`.finder-step[data-step="${finderCurrentStep}"]`).addClass('active');
            updateFinderProgress();
            updateFinderNavigation();
        }
    });

    // Option click handler
    $('.finder-option').on('click', function() {
        const $this = $(this);
        const field = $this.data('field');
        const value = $this.data('value');

        // Remove selected from siblings
        $this.siblings('.finder-option').removeClass('selected');
        $this.addClass('selected');

        // Store answer
        finderAnswers[field] = value;
    });

    // Checkbox click handler
    $('.finder-checkbox').on('click', function(e) {
        e.preventDefault(); // Prevent default label toggle behavior
        e.stopPropagation(); // Stop event bubbling

        const $this = $(this);
        const $input = $this.find('input');
        const value = $this.data('value');

        // Toggle checked state
        $this.toggleClass('checked');
        $input.prop('checked', $this.hasClass('checked'));

        // Handle "No specific needs" option
        if (value === 'none' && $this.hasClass('checked')) {
            // Uncheck all others
            $('.finder-checkbox').not($this).removeClass('checked').find('input').prop('checked', false);
            finderAnswers.protection = ['none'];
        } else if (value !== 'none') {
            // Uncheck "none" if checking something else
            $('.finder-checkbox[data-value="none"]').removeClass('checked').find('input').prop('checked', false);

            // Update protection array
            finderAnswers.protection = [];
            $('.finder-checkbox.checked input').each(function() {
                if ($(this).val() !== 'none') {
                    finderAnswers.protection.push($(this).val());
                }
            });
        }
    });

    // Previous button
    $('#finder-prev-btn').on('click', function() {
        if (finderCurrentStep > 1) {
            // If on results, go back to step 3 (Protection)
            if ($('.finder-step[data-step="results"]').hasClass('active')) {
                $('.finder-step').removeClass('active');
                $('.finder-step[data-step="3"]').addClass('active');
                finderCurrentStep = 3;
            } else {
                finderCurrentStep--;
                $('.finder-step').removeClass('active');
                $(`.finder-step[data-step="${finderCurrentStep}"]`).addClass('active');
            }
            updateFinderProgress();
            updateFinderNavigation();
        }
    });

    // Next button
    $('#finder-next-btn').on('click', function() {
        // Capture data from current step before moving
        if (finderCurrentStep === 1) {
            // Capture free text from step 1
            finderAnswers.freeText = $('#finder-freetext').val().trim();
        }

        if (finderCurrentStep < finderTotalSteps) {
            finderCurrentStep++;
            $('.finder-step').removeClass('active');
            $(`.finder-step[data-step="${finderCurrentStep}"]`).addClass('active');
            updateFinderProgress();
            updateFinderNavigation();
        } else if (finderCurrentStep === finderTotalSteps) {
            // Validate: at least one step must have input
            const hasFreeText = finderAnswers.freeText && finderAnswers.freeText.trim().length > 0;
            const hasBudget = finderAnswers.budget && finderAnswers.budget.length > 0;
            const hasProtection = finderAnswers.protection && finderAnswers.protection.length > 0 && !(finderAnswers.protection.length === 1 && finderAnswers.protection[0] === 'none');

            if (!hasFreeText && !hasBudget && !hasProtection) {
                $('#finderValidationModal').modal('show');
                return;
            }

            // Show results and call AI
            $('.finder-step').removeClass('active');
            $('.finder-step[data-step="results"]').addClass('active');
            finderCurrentStep++;
            updateFinderNavigation();
            findMatchingVarietiesWithAI();
        } else {
            // Close modal
            $('#varietyFinderModal').modal('hide');
        }
    });

    // All steps are now optional (users can skip any step)
    // No validation needed - AI will work with whatever input is provided

    // Find matching varieties using AI analysis
    function findMatchingVarietiesWithAI() {
        // Show loading
        $('.finder-results-loading').removeClass('d-none');
        $('.finder-results-list').addClass('d-none').empty();
        $('.finder-no-results').addClass('d-none');
        $('.finder-ai-summary').addClass('d-none');
        $('.finder-results-subtitle').text('Sinusuri ng aming Smart Technician ang iyong mga pangangailangan...');

        // Get current crop type and breed/corn type from main wizard
        const cropType = $('#crop_type').val();
        const breedType = $('#breed_type').val();
        const cornType = $('#corn_type').val();

        // Get farm info from main wizard (Steps 3, 4, 5)
        const farmSize = $('#farm_size').val();
        const farmUnit = $('#farm_unit').val();
        const province = $('#province').val();
        const municipality = $('#municipality').val();
        const croppingSeason = $('#cropping_season').val();
        const mainGoal = $('#main_goal').val();

        console.log('=== AI Variety Finder ===');
        console.log('Main form selections:', { cropType, breedType, cornType, farmSize, farmUnit, province, municipality, croppingSeason, mainGoal });
        console.log('Finder wizard answers:', finderAnswers);

        // Prepare data for AI endpoint
        const requestData = {
            _token: '{{ csrf_token() }}',
            freeText: finderAnswers.freeText,
            budget: finderAnswers.budget,
            farmSize: farmSize,
            farmUnit: farmUnit,
            province: province,
            municipality: municipality,
            croppingSeason: croppingSeason,
            mainGoal: mainGoal,
            protection: finderAnswers.protection,
            cropType: cropType,
            breedType: breedType,
            cornType: cornType
        };

        $.ajax({
            url: '{{ route("recommendation-generate.ai-recommend") }}',
            type: 'POST',
            data: requestData,
            timeout: 90000, // 90 second timeout for AI processing
            success: function(response) {
                console.log('AI Response:', response);
                $('.finder-results-loading').addClass('d-none');

                if (response.success && response.recommendations) {
                    // Update subtitle
                    $('.finder-results-subtitle').text('Batay sa iyong mga kagustuhan, narito ang aming mga nangungunang mungkahi');

                    // Show AI summary if available
                    if (response.recommendations.summary) {
                        $('#finder-ai-summary-text').text(response.recommendations.summary);
                        $('.finder-ai-summary').removeClass('d-none');
                    }

                    // Display results
                    if (response.recommendations.items && response.recommendations.items.length > 0) {
                        displayAIFinderResults(response.recommendations.items);
                    } else {
                        showNoResults('Walang nahanap na katugmang varieties. Subukang baguhin ang iyong mga criteria.');
                    }
                } else {
                    showNoResults(response.message || 'Hindi nakuha ang mga rekomendasyon ng Smart Technician.');
                }
            },
            error: function(xhr) {
                console.error('AI Error:', xhr);
                $('.finder-results-loading').addClass('d-none');

                let errorMsg = 'Hindi nakuha ang mga rekomendasyon ng Smart Technician.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.needsSetup) {
                        errorMsg = xhr.responseJSON.message;
                    } else {
                        errorMsg = xhr.responseJSON.message || errorMsg;
                    }
                }
                showNoResults(errorMsg);
            }
        });
    }

    // Get match score color: green (100%) → orange (0%)
    function getMatchColor(score) {
        score = Math.max(0, Math.min(100, score));
        // Green: rgb(52, 195, 143) at 100%  →  Orange: rgb(241, 180, 76) at 0%
        const r = Math.round(241 + (52 - 241) * (score / 100));
        const g = Math.round(180 + (195 - 180) * (score / 100));
        const b = Math.round(76 + (143 - 76) * (score / 100));
        return `rgb(${r}, ${g}, ${b})`;
    }

    // Display AI finder results with reasons
    function displayAIFinderResults(results) {
        let html = '';
        results.forEach(function(variety, index) {
            const rankClass = index === 0 ? 'gold' : (index === 1 ? 'silver' : (index === 2 ? 'bronze' : ''));
            const score = variety.matchScore || 85;
            const matchColor = getMatchColor(score);

            html += `
                <div class="finder-result-item" data-id="${variety.id}" data-name="${escapeHtml(variety.name)}" data-manufacturer="${escapeHtml(variety.manufacturer || '')}" data-yield="${escapeHtml(variety.potentialYield || '')}">
                    <div class="result-rank ${rankClass}">${variety.rank || (index + 1)}</div>
                    ${variety.imagePath ?
                        `<img src="{{ asset('') }}${variety.imagePath}" alt="" class="result-image">` :
                        `<div class="result-image-placeholder"><i class="bx bx-leaf text-success"></i></div>`
                    }
                    <div class="result-info">
                        <div class="result-name">${escapeHtml(variety.name)}</div>
                        <div class="result-meta">${escapeHtml(variety.manufacturer || 'Hindi Tiyak')} ${variety.potentialYield ? '• ' + escapeHtml(variety.potentialYield) : ''}</div>
                        ${variety.reason ? `<div class="result-reason">${escapeHtml(variety.reason)}</div>` : ''}
                    </div>
                    <div class="result-match" style="background:${matchColor}">${score}%</div>
                </div>
            `;
        });

        $('.finder-results-list').html(html).removeClass('d-none');
    }

    // Show no results with custom message
    function showNoResults(message) {
        $('.finder-results-loading').addClass('d-none');
        $('.finder-results-list').addClass('d-none');
        $('.finder-ai-summary').addClass('d-none');
        $('#finder-error-message').text(message || 'Walang nahanap na katugmang varieties. Subukang mag-browse ng mga varieties nang manu-mano.');
        $('.finder-no-results').removeClass('d-none');
    }

    // Select variety from finder results
    $(document).on('click', '.finder-result-item', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const manufacturer = $(this).data('manufacturer');
        const yieldValue = $(this).data('yield');

        // Select the variety
        selectVariety(id, name, manufacturer, yieldValue);

        // Close modal
        $('#varietyFinderModal').modal('hide');

        toastr.success(`Napili ang "${name}" batay sa iyong mga kagustuhan!`, 'Napili ang Variety');
    });

    // Reset finder when modal is closed
    $('#varietyFinderModal').on('hidden.bs.modal', function() {
        // Don't reset if user selected a variety (already closed)
    });

    // Collapsible toggle icon for soil-info-modal sections
    $('.soil-info-modal .info-section-toggle').on('click', function() {
        var $toggle = $(this);
        var target = $toggle.data('bs-target');
        $(target).on('shown.bs.collapse', function() {
            $toggle.removeClass('collapsed');
        }).on('hidden.bs.collapse', function() {
            $toggle.addClass('collapsed');
        });
    });
</script>
@endsection

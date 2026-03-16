{{-- AIO (AI SEO Optimization) Settings --}}
<div class="accordion settings-accordion" id="aioAccordion">
    {{-- AI Overview Optimization --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#aioOverview" aria-expanded="true">
                <i class="bx bx-brain accordion-icon"></i>AI Overview Optimization
            </button>
        </h2>
        <div id="aioOverview" class="accordion-collapse collapse show" data-bs-parent="#aioAccordion">
            <div class="accordion-body">
                <p class="text-secondary small mb-3"><i class="bx bx-info-circle me-1"></i>Optimize content for AI assistants like Google AI Overview, ChatGPT, Perplexity, and Bing Copilot.</p>

                <div class="mb-2">
                    <label class="form-label text-dark">AI Summary (Concise Description)</label>
                    <textarea class="form-control section-setting-input" data-section="aio_settings" data-setting="aiSummary" rows="3" placeholder="A clear, factual 2-3 sentence description that AI can cite as a direct answer">{{ $section->getSetting('aiSummary', '') }}</textarea>
                    <small class="text-secondary">This summary helps AI assistants provide accurate information about your site. Keep it factual and concise.</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Primary Topic/Niche</label>
                        <input type="text" class="form-control section-setting-input" data-section="aio_settings" data-setting="primaryTopic"
                               value="{{ $section->getSetting('primaryTopic', '') }}" placeholder="e.g., Philippine Agriculture Training">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Target Audience</label>
                        <input type="text" class="form-control section-setting-input" data-section="aio_settings" data-setting="targetAudience"
                               value="{{ $section->getSetting('targetAudience', '') }}" placeholder="e.g., Filipino farmers, agricultural entrepreneurs">
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label text-dark">Key Offerings (Comma-separated)</label>
                    <input type="text" class="form-control section-setting-input" data-section="aio_settings" data-setting="keyOfferings"
                           value="{{ $section->getSetting('keyOfferings', '') }}" placeholder="e.g., Rice farming courses, Corn cultivation training, Agricultural consulting">
                </div>
            </div>
        </div>
    </div>

    {{-- Structured Q&A for AI --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#aioFaq">
                <i class="bx bx-help-circle accordion-icon"></i>AI-Optimized FAQ
            </button>
        </h2>
        <div id="aioFaq" class="accordion-collapse collapse" data-bs-parent="#aioAccordion">
            <div class="accordion-body">
                <p class="text-secondary small mb-3"><i class="bx bx-info-circle me-1"></i>Create Q&A pairs that AI assistants can use to answer common queries about your business.</p>

                <div class="faq-item mb-3 p-3 bg-light rounded">
                    <label class="form-label text-dark fw-semibold">Q1: What is Ani-Senso?</label>
                    <textarea class="form-control section-setting-input mb-2" data-section="aio_settings" data-setting="faqQ1Answer" rows="2" placeholder="Clear, factual answer for AI to cite">{{ $section->getSetting('faqQ1Answer', '') }}</textarea>
                </div>

                <div class="faq-item mb-3 p-3 bg-light rounded">
                    <label class="form-label text-dark fw-semibold">Q2: What courses are offered?</label>
                    <textarea class="form-control section-setting-input mb-2" data-section="aio_settings" data-setting="faqQ2Answer" rows="2">{{ $section->getSetting('faqQ2Answer', '') }}</textarea>
                </div>

                <div class="faq-item mb-3 p-3 bg-light rounded">
                    <label class="form-label text-dark fw-semibold">Q3: Who are the courses for?</label>
                    <textarea class="form-control section-setting-input mb-2" data-section="aio_settings" data-setting="faqQ3Answer" rows="2">{{ $section->getSetting('faqQ3Answer', '') }}</textarea>
                </div>

                <div class="faq-item mb-3 p-3 bg-light rounded">
                    <label class="form-label text-dark fw-semibold">Q4: How to get started?</label>
                    <textarea class="form-control section-setting-input mb-2" data-section="aio_settings" data-setting="faqQ4Answer" rows="2">{{ $section->getSetting('faqQ4Answer', '') }}</textarea>
                </div>

                <div class="faq-item mb-3 p-3 bg-light rounded">
                    <label class="form-label text-dark fw-semibold">Q5: What makes Ani-Senso different?</label>
                    <textarea class="form-control section-setting-input" data-section="aio_settings" data-setting="faqQ5Answer" rows="2">{{ $section->getSetting('faqQ5Answer', '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Entity & Knowledge Graph --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#aioEntity">
                <i class="bx bx-network-chart accordion-icon"></i>Entity & Knowledge Graph
            </button>
        </h2>
        <div id="aioEntity" class="accordion-collapse collapse" data-bs-parent="#aioAccordion">
            <div class="accordion-body">
                <p class="text-secondary small mb-3"><i class="bx bx-info-circle me-1"></i>Define your organization's identity for AI knowledge graphs and entity recognition.</p>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Organization Type</label>
                        <select class="form-select section-setting-input" data-section="aio_settings" data-setting="organizationType">
                            <option value="EducationalOrganization" {{ $section->getSetting('organizationType') == 'EducationalOrganization' ? 'selected' : '' }}>Educational Organization</option>
                            <option value="OnlineBusiness" {{ $section->getSetting('organizationType') == 'OnlineBusiness' ? 'selected' : '' }}>Online Business</option>
                            <option value="LocalBusiness" {{ $section->getSetting('organizationType') == 'LocalBusiness' ? 'selected' : '' }}>Local Business</option>
                            <option value="Corporation" {{ $section->getSetting('organizationType') == 'Corporation' ? 'selected' : '' }}>Corporation</option>
                            <option value="NGO" {{ $section->getSetting('organizationType') == 'NGO' ? 'selected' : '' }}>NGO / Non-Profit</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Industry/Sector</label>
                        <input type="text" class="form-control section-setting-input" data-section="aio_settings" data-setting="industrySector"
                               value="{{ $section->getSetting('industrySector', '') }}" placeholder="e.g., Agricultural Education">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Founded Year</label>
                        <input type="text" class="form-control section-setting-input" data-section="aio_settings" data-setting="foundedYear"
                               value="{{ $section->getSetting('foundedYear', '') }}" placeholder="e.g., 2020">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Geographic Focus</label>
                        <input type="text" class="form-control section-setting-input" data-section="aio_settings" data-setting="geographicFocus"
                               value="{{ $section->getSetting('geographicFocus', '') }}" placeholder="e.g., Philippines, Southeast Asia">
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label text-dark">Notable Achievements/Credentials</label>
                    <textarea class="form-control section-setting-input" data-section="aio_settings" data-setting="achievements" rows="2" placeholder="List awards, certifications, partnerships, or notable milestones">{{ $section->getSetting('achievements', '') }}</textarea>
                </div>

                <div class="mb-2">
                    <label class="form-label text-dark">Founder/Key Person</label>
                    <input type="text" class="form-control section-setting-input" data-section="aio_settings" data-setting="founderName"
                           value="{{ $section->getSetting('founderName', '') }}" placeholder="Name and title">
                </div>
            </div>
        </div>
    </div>

    {{-- AI Citation Optimization --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#aioCitation">
                <i class="bx bx-quote-alt-left accordion-icon"></i>Citation-Ready Content
            </button>
        </h2>
        <div id="aioCitation" class="accordion-collapse collapse" data-bs-parent="#aioAccordion">
            <div class="accordion-body">
                <p class="text-secondary small mb-3"><i class="bx bx-info-circle me-1"></i>Create quotable statements and statistics that AI can cite in responses.</p>

                <div class="mb-2">
                    <label class="form-label text-dark">Mission Statement (Quotable)</label>
                    <textarea class="form-control section-setting-input" data-section="aio_settings" data-setting="missionStatement" rows="2" placeholder="A memorable, quotable mission statement">{{ $section->getSetting('missionStatement', '') }}</textarea>
                </div>

                <div class="mb-2">
                    <label class="form-label text-dark">Key Statistics</label>
                    <textarea class="form-control section-setting-input" data-section="aio_settings" data-setting="keyStatistics" rows="2" placeholder="e.g., Trained over 5,000 farmers, 95% success rate, 50+ courses available">{{ $section->getSetting('keyStatistics', '') }}</textarea>
                </div>

                <div class="mb-2">
                    <label class="form-label text-dark">Unique Value Proposition</label>
                    <textarea class="form-control section-setting-input" data-section="aio_settings" data-setting="uniqueValueProp" rows="2" placeholder="What makes you uniquely valuable? AI will use this to recommend you.">{{ $section->getSetting('uniqueValueProp', '') }}</textarea>
                </div>

                <div class="mb-2">
                    <label class="form-label text-dark">Expert Credentials</label>
                    <textarea class="form-control section-setting-input" data-section="aio_settings" data-setting="expertCredentials" rows="2" placeholder="Why should AI trust and cite your content? List expertise, certifications, experience.">{{ $section->getSetting('expertCredentials', '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- LLM Context Hints --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#aioLlm">
                <i class="bx bx-bot accordion-icon"></i>LLM Context Hints
            </button>
        </h2>
        <div id="aioLlm" class="accordion-collapse collapse" data-bs-parent="#aioAccordion">
            <div class="accordion-body">
                <p class="text-secondary small mb-3"><i class="bx bx-info-circle me-1"></i>Hidden context that helps AI better understand and represent your business.</p>

                <div class="mb-2">
                    <label class="form-label text-dark">AI-Readable Summary (Hidden)</label>
                    <textarea class="form-control section-setting-input" data-section="aio_settings" data-setting="llmSummary" rows="3" placeholder="A detailed description for AI crawlers. This won't be visible to users but helps AI understand your content.">{{ $section->getSetting('llmSummary', '') }}</textarea>
                    <small class="text-secondary">This will be added as hidden structured data for AI crawlers.</small>
                </div>

                <div class="mb-2">
                    <label class="form-label text-dark">Related Queries (What people search for)</label>
                    <textarea class="form-control section-setting-input" data-section="aio_settings" data-setting="relatedQueries" rows="2" placeholder="e.g., How to grow rice in Philippines, Best farming courses, Agriculture training near me">{{ $section->getSetting('relatedQueries', '') }}</textarea>
                </div>

                <div class="mb-2">
                    <label class="form-label text-dark">Competitor Alternatives (What you replace)</label>
                    <input type="text" class="form-control section-setting-input" data-section="aio_settings" data-setting="competitorAlternatives"
                           value="{{ $section->getSetting('competitorAlternatives', '') }}" placeholder="e.g., Traditional farming seminars, Expensive agricultural consultants">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Content Freshness</label>
                        <select class="form-select section-setting-input" data-section="aio_settings" data-setting="contentFreshness">
                            <option value="frequently" {{ $section->getSetting('contentFreshness', 'frequently') == 'frequently' ? 'selected' : '' }}>Updated Frequently</option>
                            <option value="monthly" {{ $section->getSetting('contentFreshness') == 'monthly' ? 'selected' : '' }}>Updated Monthly</option>
                            <option value="quarterly" {{ $section->getSetting('contentFreshness') == 'quarterly' ? 'selected' : '' }}>Updated Quarterly</option>
                            <option value="evergreen" {{ $section->getSetting('contentFreshness') == 'evergreen' ? 'selected' : '' }}>Evergreen Content</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Content Authority Level</label>
                        <select class="form-select section-setting-input" data-section="aio_settings" data-setting="authorityLevel">
                            <option value="expert" {{ $section->getSetting('authorityLevel', 'expert') == 'expert' ? 'selected' : '' }}>Expert/Primary Source</option>
                            <option value="professional" {{ $section->getSetting('authorityLevel') == 'professional' ? 'selected' : '' }}>Professional/Industry</option>
                            <option value="educational" {{ $section->getSetting('authorityLevel') == 'educational' ? 'selected' : '' }}>Educational Resource</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#aioSeo">
                <i class="bx bx-search-alt accordion-icon"></i>Section SEO
            </button>
        </h2>
        <div id="aioSeo" class="accordion-collapse collapse" data-bs-parent="#aioAccordion">
            <div class="accordion-body">
                @include('aniSensoAdmin.homepage-settings.partials._seo-section', ['section' => $section, 'sectionKey' => 'aio_settings'])
            </div>
        </div>
    </div>
</div>

# AI architecture roadmap

AI/RAG is not implemented in the current standalone milestone. Any future implementation must run as an optional local plugin module and may access only records permitted to the requesting user and organization. Standalone operation may never require a hosted model provider.

Every sourced claim must return citations to accessible records. Outputs must separate sourced facts, calculations, assumptions, and model interpretation. Missing data must remain missing. Audit records will retain the requested task, tools/data accessed, concise tool provenance, final sources, timestamps, model/version, user, and organization—never private chain-of-thought. Prompt-injection resistance, document trust labels, data-loss prevention, model evaluation, and token/cost limits are release gates.

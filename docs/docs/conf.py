# Configuration file for the Sphinx documentation builder.

# -- Project information

project = 'Yokai Batch'
copyright = '2019, Yann Eugoné'
author = 'Yann Eugoné'

release = '0.5.0'
version = '0.5.0'

# -- General configuration

extensions = [
    'sphinx.ext.duration',
    'sphinx.ext.doctest',
    'sphinx.ext.autodoc',
    'sphinx.ext.autosummary',
    'sphinx.ext.intersphinx',
]

intersphinx_mapping = {
    'python': ('https://docs.python.org/3/', None),
    'sphinx': ('https://www.sphinx-doc.org/en/master/', None),
}
intersphinx_disabled_domains = ['std']

templates_path = ['_templates']

html_static_path = ['_static']

html_css_files = [
    'styles/custom.css',
]

# -- Options for HTML output

html_theme = 'sphinx_rtd_theme'

# -- Options for EPUB output

epub_show_urls = 'footnote'

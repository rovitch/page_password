## Setup
### 🧾 1. Create a new login page
- In the **Web** → **Page module**, create a new page and note its UID.
- Add a new content element: **Forms** section → **Page Password**
- Configure styling options (colors, logo) or use defaults
- Ensure the page is accessible

### 🛠️ 2. Site configuration

#### TYPO3 Backend (recommended):
- Go to **Site Management → Sites**
- Edit your site configuration
- Go to **Page protection** and set **Default login page** field to your login page

#### YAML method:
Edit `config/sites/[site-identifier]/config.yaml` and add the following setting:
``` yaml
pagepassword_default_login_page: 't3://page?uid=xxx'
```
Replace `xxx` with your login page UID.

### 🛡️ 3. Protect a page
- Edit target page → **Page protection** tab
- Check **Enable page password protection**
- Set password
- Optionally check **Extend to subpages** for tree protection

### 🎨 4. Configure a login page with a custom layout (optional)
This is optional but recommended; it replaces your site's usual layout rendering (no header and no footer) for that login page.
Otherwise, you may need to adjust the CSS to fit your site's layout.

<details>
  <summary>1. Create a new backend layout</summary>

  Register a new backend layout in your site package or directly in TYPO3 backend.

  ```
    mod {
      web_layout {
        BackendLayouts {
          pagepassword_login {
            title = Page password login
            config {
              backend_layout {
                colCount = 1
                rowCount = 1
                rows {
                  1 {
                    columns {
                      1 {
                        identifier = main
                        name = Form
                        colPos = 0
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  ```
</details>

<details>
  <summary>2. Add corresponding typoscript</summary>

  Add typoscript in your site package or directly in TYPO3 Backend.

  ```
    [page["backend_layout"] == 'pagets__pagepassword_login']
      page >
      page = PAGE
      page.typeNum = 0
      page.includeCSS.reset = EXT:page_password/Resources/Public/assets/css/reset.min.css
      page {
        10 = CONTENT
        10 {
          table = tt_content
          select {
            orderBy = sorting
            where = {#colPos}=0
          }
        }
      }
    [end]
  ```
</details>

<details>
  <summary>3. Use the newly created backend layout</summary>

  Edit login page properties and under **Appearance** → **Backend Layout** select the newly created layout.
</details>

> [!WARNING]
> Typoscript **MUST** be added **AFTER** your main typoscript PAGE declaration

> [!NOTE]
> If you registered the layout directly in the TYPO3 backend, replace: `[page["backend_layout"] == 'pagets__pagepassword_login']` with `[page["backend_layout"] == 'xxx']` where `xxx` is the backend layout uid

### ✅ 5. Test & Troubleshoot
**Test steps:**
- Clear all caches
- Visit protected page
- Verify redirection to the login page works
- Test password authentication

**Common issues:**
- Wrong UID in site configuration
- Login page not accessible
- Caches not cleared after changes

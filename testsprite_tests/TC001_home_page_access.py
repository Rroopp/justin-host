import requests

def test_home_page_access():
    url = "http://localhost:8000/"
    try:
        response = requests.get(url, timeout=30)
        response.raise_for_status()
        assert response.status_code == 200, f"Expected status code 200, got {response.status_code}"
        assert response.text, "Home page response is empty."
    except requests.RequestException as e:
        assert False, f"Request to home page failed: {e}"

test_home_page_access()
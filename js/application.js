'use strict';
// Get React hook from the global object.
// See more info on hooks if needed https://reactjs.org/docs/hooks-intro.html.
const {useState, useEffect} = React;
// Grab html placeholder from field formatter output.
const rootElement = document.getElementById('todo-list');
// Get json data with field content from Drupal.
const todoList = JSON.parse(rootElement.dataset.todoList) || [];
// Get whether user authenticated
const authenticated = rootElement.dataset.authenticated === 'true';
// Drupal rest session token path
const restSessionTokenPath = '/session/token';
// Drupal rest resource API path
const restApiPathToDoChecklist = '/api/todo_checklist/' + todoList['nid'];

// This is JSX Component for the whole To-Do List application.
const Application = () => {
    // React hooks to handle the original state of To-Do List as well as to
    // manage changes of its state.
    const [todoItems, setTodoItems] = useState(todoList['items']);
    // React hooks to store XCSRF token.
    const [csrfToken, setCsrfToken] = useState();
    // React hooks to check whether fetching (loading/saving).
    const [fetching, setFetching] = useState();
    // React hooks to check whether currently saving todoItem
    // to prevent race condition possibility of being clicked multiple times
    // before saved.
    const [savingItem, setSavingItem] = useState([]);

    useEffect(() => {
      // Get Csrf early. can get it again later if expires.
      getCsrf().then();
    }, []);
    // Get a REST Session Token asap. Also set "fetching" var
    // so save can't be triggered until CSRF token is ready.
    async function getCsrf() {
      setFetching(true);
      const response = await fetch(restSessionTokenPath, {
        method: 'GET',
        mode: 'same-origin',
        cache: 'no-cache',
        credentials: 'same-origin',
      }).catch(e => {
        console.log(e);
      });
      response.text().then(function(text) {
        setCsrfToken(
          {token: text, expires: response.headers.get('expires')});
        setFetching(false);
      });
    };
    // React to the change event of checkbox of a To-Do item.
    const onCheckboxChange = (event) => {
      // Get value of the paragraph ID from the triggered element.
      const id = Number.parseInt(event.target.value, 10);
      // Get index of the element in the list of To-Do items.
      const itemIndex = todoItems.findIndex(todoItem => todoItem.id === id);
      // Deep clone the array to make sure that we don't drag our changes
      // to the mutable array with state.
      let newTodoItems = [...todoItems];
      // Update state of the To-Do item.
      newTodoItems[itemIndex].completed = event.target.checked;
      setTodoItems(newTodoItems);
      // Send To-Do *item* update to Drupal API. Have avoided race conditions
      // on same item, therefore can also do single items in parallel if user
      // wishes.
      sendDrupal(newTodoItems[itemIndex]).catch((e) => {
        console.log(e);
        // Revert checkbox change if save fails.
        let newTodoItems = [...todoItems];
        newTodoItems[itemIndex].completed = !event.target.checked;
        setTodoItems(newTodoItems);
      });
    };

    const sendDrupal = (newTodoItem) => {
      return new Promise(function(resolve, reject) {
          let json;
          try {
            json = JSON.stringify(newTodoItem);
          }
          catch (e) {
            reject(Error(e));
          }
          patchData(newTodoItem['id']).catch((e) => {
            reject(Error(e));
          });

          // Send to API.
          async function patchData(id) {
            setSavingItem(
              (savingItem) => ({...savingItem, [newTodoItem['id']]: true}));
            fetch(restApiPathToDoChecklist, {
              method: 'PATCH',
              mode: 'same-origin',
              cache: 'no-cache',
              credentials: 'same-origin',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken.token,
              },
              referrerPolicy: 'strict-origin-when-cross-origin',
              body: json,
            }).then((response) => {
              if (response.ok) {
                let data = response.json();
                return (data);
              }
              else if (!response.ok) {
                response.clone().json().catch(() =>
                  response.text()).then(text => {
                  if (text.message && (text.message ===
                    'X-CSRF-Token request header is missing' ||
                    'X-CSRF-Token request header is invalid')) {
                    getCsrf().then(() => { patchData(id); });
                  }
                  else {
                    reject(text);
                  }
                });
              }
            }).
              catch((e) => {
                reject(Error("Invalid Response " + e));
              }).
              finally(() => {
                setSavingItem(
                  (savingItem) => ({...savingItem, [newTodoItem['id']]: false}));
              });
          }
        },
      );
    };

    return (
      <div className="todo-list">
        { // I added authorisation check to frontend - extra feature.
          !authenticated ? <h3><a href={"/user/login"}>Log in</a> to be able
            to
            save changes...</h3> : ''}
        {todoList.items.map(item => {
          return (
            <div className="todo-list__item" key={item.id}>
              <input
                type="checkbox"
                value={item.id}
                id={"item-" + item.id}
                name={"item-" + item.id}
                className="todo-list__input"
                checked={todoItems.find(
                  todoItem => todoItem.id === item.id).completed}
                onChange={onCheckboxChange}
                // You can't save any item whilst still fetching session
                // token. You can't call another save of same item while save
                // fetch. Also "permission to modify state of To-Do items
                // (not the node!) should be given only to the users who have
                // access to view the checklist".
                disabled={(fetching !== false) ||
                  (savingItem[item.id] === true) || todoList['disabled'] ===
                  true}
              />
              <label
                htmlFor={"item-" + item.id}
                className="todo-list__item-label"
                dangerouslySetInnerHTML={{__html: item.label}}
              />
            </div>
          );
        })}
      </div>
    );
  }
;

// Render react component inside the field's html.
const root = ReactDOM.createRoot(rootElement);
root.render(<Application/>);

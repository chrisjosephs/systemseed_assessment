'use strict';
// Get React hook from the global object.
// See more info on hooks if needed https://reactjs.org/docs/hooks-intro.html.
const {useState, useEffect} = React;
// Grab html placeholder from field formatter output.
const rootElement = document.getElementById('todo-list');
// Get json data with field content from Drupal.
const todoList = JSON.parse(rootElement.dataset.todoList) || [];
// Drupal rest session token path
const restSessionTokenPath = '/session/token';
// Drupal rest resource API path
const restApiPath = '/api/todolist';

// This is JSX Component for the whole To-Do List application.
const Application = () => {
  // React hooks to handle the original state of To-Do List as well as to
  // manage changes of its state.
  const [todoItems, setTodoItems] = useState(todoList);
  // React hooks to store XCSRF token
  const [csrfToken, setCsrfToken] = useState();

  // get a REST Session Token asap
  useEffect(() => {
    const resp = fetch(restSessionTokenPath, {
      method: 'GET',
      mode: 'same-origin',
      cache: 'no-cache',
      credentials: 'same-origin',
    }).then(response => response.text())
      .then(data => {
      setCsrfToken(data);
      console.log(data);
    });
  });

  const componentDidMount = () => {
    console.log("componentWillMount");

  };
  // React to the change event of checkbox of a To-Do item.
  const onCheckboxChange = (event) => {
    // Get value of the paragraph ID from the triggered element.
    const id = Number.parseInt(event.target.value, 10);
    // Get index of the element in the list of To-Do items.
    const itemIndex = todoItems.findIndex(todoItem => todoItem.id === id);
    // Deep clone the array to make sure that we don't drag our changes
    // to the mutable array with state.
    const newTodoItems = [...todoItems];
    // Update state of the To-Do item.
    newTodoItems[itemIndex].completed = event.target.checked;
    setTodoItems(newTodoItems);
    // Send To-Do item update to Drupal API
    sendDrupal(newTodoItems);
  };

  const sendDrupal = (newTodoItems) => {
    console.log(newTodoItems);
    let json;
    // convert object to JSON string
    try {
      json = JSON.stringify(newTodoItems);
    }
    catch (e) {
      console.log(e);// you can get error here
    }
    postData();

    // sent to API
    async function postData() {
      const response = await fetch(restApiPath, {
        method: 'POST', // *GET, POST, PUT, DELETE, etc.
        mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache,
                           // only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken,
        },
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer,
                                       // *no-referrer-when-downgrade, origin,
                                       // origin-when-cross-origin,
                                       // same-origin, strict-origin,
                                       // strict-origin-when-cross-origin,
                                       // unsafe-url
        body: json, // body data type must match "Content-Type" header
      });
      console.log(response);
      return response.json(); // parses JSON response into native JavaScript
                              // objects
    }
  };

  return (
    <div className="todo-list">
      {todoList.map(item => {
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
};

// Render react component inside the field's html.
const root = ReactDOM.createRoot(rootElement);
root.render(<Application/>);

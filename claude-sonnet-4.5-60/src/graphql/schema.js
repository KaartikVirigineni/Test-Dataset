const { buildSchema } = require('graphql');

const schema = buildSchema(`
  type User {
    id: Int!
    username: String!
  }

  type AuthPayload {
    id: Int!
    username: String!
    token: String!
  }

  type Task {
    id: Int!
    user_id: Int!
    title: String!
    description: String
    status: String!
    priority: String!
    created_at: String!
    updated_at: String!
  }

  input TaskInput {
    title: String!
    description: String
    status: String
    priority: String
  }

  input TaskUpdateInput {
    title: String
    description: String
    status: String
    priority: String
  }

  type Query {
    tasks(status: String, priority: String): [Task!]!
    task(id: Int!): Task
  }

  type Mutation {
    register(username: String!, password: String!): AuthPayload!
    login(username: String!, password: String!): AuthPayload!
    createTask(input: TaskInput!): Task!
    updateTask(id: Int!, input: TaskUpdateInput!): Task!
    deleteTask(id: Int!): Boolean!
  }
`);

module.exports = schema;